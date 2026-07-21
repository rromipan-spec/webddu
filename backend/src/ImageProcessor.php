<?php
declare(strict_types=1);

final class ImageProcessor
{
    private const MAX_WIDTH = 6000;
    private const MAX_HEIGHT = 6000;
    private const MAX_PIXELS = 24000000;

    private const VARIANTS = [
        'thumb' => ['width' => 360, 'height' => 240, 'crop' => true, 'format' => 'webp', 'quality' => 78],
        'card' => ['width' => 800, 'height' => 520, 'crop' => true, 'format' => 'webp', 'quality' => 82],
        'content' => ['width' => 1440, 'height' => 1800, 'crop' => false, 'format' => 'webp', 'quality' => 84],
        'hero' => ['width' => 1920, 'height' => 1080, 'crop' => true, 'format' => 'webp', 'quality' => 84],
        // JPEG dipakai untuk kompatibilitas thumbnail WhatsApp, Facebook, dan crawler lain.
        'social' => ['width' => 1200, 'height' => 630, 'crop' => true, 'format' => 'jpg', 'quality' => 86],
    ];

    public static function process(
        string $sourcePath,
        string $sourceMime,
        string $uploadRoot,
        bool $keepOriginal = false
    ): array {
        self::assertAvailable();
        $info = @getimagesize($sourcePath);
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width < 1 || $height < 1) {
            throw new InvalidArgumentException('Resolusi gambar tidak dapat dibaca.');
        }
        if ($width > self::MAX_WIDTH || $height > self::MAX_HEIGHT || ($width * $height) > self::MAX_PIXELS) {
            throw new InvalidArgumentException('Resolusi gambar terlalu besar. Maksimal 6000×6000 dan 24 megapiksel.');
        }

        self::ensureDirectory($uploadRoot, 0755);
        $id = bin2hex(random_bytes(16));
        $directory = rtrim($uploadRoot, '/\\') . DIRECTORY_SEPARATOR . $id;
        self::ensureDirectory($directory, 0755);

        try {
            $source = self::loadImage($sourcePath, $sourceMime);
            $source = self::orientJpeg($source, $sourcePath, $sourceMime);
            $sourceWidth = imagesx($source);
            $sourceHeight = imagesy($source);
            $urls = [];

            foreach (self::VARIANTS as $name => $settings) {
                $variant = $settings['crop']
                    ? self::crop($source, $sourceWidth, $sourceHeight, $settings['width'], $settings['height'])
                    : self::contain($source, $sourceWidth, $sourceHeight, $settings['width'], $settings['height']);
                $extension = $settings['format'] === 'jpg' ? 'jpg' : 'webp';
                $path = $directory . DIRECTORY_SEPARATOR . $name . '.' . $extension;
                self::save($variant, $path, $settings['format'], $settings['quality']);
                imagedestroy($variant);
                chmod($path, 0644);
                $urls[$name] = '/uploads/' . $id . '/' . $name . '.' . $extension;
            }

            imagedestroy($source);

            if ($keepOriginal) {
                $extension = match ($sourceMime) {
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => throw new InvalidArgumentException('Format gambar asli tidak didukung.'),
                };
                $originalPath = $directory . DIRECTORY_SEPARATOR . 'original.' . $extension;
                if (!copy($sourcePath, $originalPath)) {
                    throw new RuntimeException('Gambar asli gagal disimpan.');
                }
                chmod($originalPath, 0644);
                $urls['original'] = '/uploads/' . $id . '/original.' . $extension;
            }

            return [
                'url' => $urls['card'],
                'variants' => $urls,
                'source' => ['width' => $width, 'height' => $height],
            ];
        } catch (Throwable $error) {
            self::removeDirectory($directory);
            throw $error;
        }
    }

    private static function assertAvailable(): void
    {
        if (!extension_loaded('gd') || !function_exists('imagewebp') || !function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('Ekstensi GD dengan dukungan WebP belum aktif di server.');
        }
    }

    private static function loadImage(string $path, string $mime)
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
        if ($image === false) {
            throw new InvalidArgumentException('Isi gambar rusak atau formatnya tidak didukung.');
        }
        return $image;
    }

    private static function orientJpeg($image, string $path, string $mime)
    {
        if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
            return $image;
        }
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
        $degrees = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($degrees === 0) {
            return $image;
        }
        $rotated = imagerotate($image, $degrees, 0);
        if ($rotated === false) {
            return $image;
        }
        imagedestroy($image);
        return $rotated;
    }

    private static function crop($source, int $sourceWidth, int $sourceHeight, int $width, int $height)
    {
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $width / $height;
        if ($sourceRatio > $targetRatio) {
            $cropHeight = $sourceHeight;
            $cropWidth = (int) round($sourceHeight * $targetRatio);
            $sourceX = (int) floor(($sourceWidth - $cropWidth) / 2);
            $sourceY = 0;
        } else {
            $cropWidth = $sourceWidth;
            $cropHeight = (int) round($sourceWidth / $targetRatio);
            $sourceX = 0;
            $sourceY = (int) floor(($sourceHeight - $cropHeight) / 2);
        }

        $target = self::canvas($width, $height);
        if (!imagecopyresampled($target, $source, 0, 0, $sourceX, $sourceY, $width, $height, $cropWidth, $cropHeight)) {
            imagedestroy($target);
            throw new RuntimeException('Gambar gagal dipotong.');
        }
        return $target;
    }

    private static function contain($source, int $sourceWidth, int $sourceHeight, int $maxWidth, int $maxHeight)
    {
        $scale = min(1, $maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
        $width = max(1, (int) round($sourceWidth * $scale));
        $height = max(1, (int) round($sourceHeight * $scale));
        $target = self::canvas($width, $height);
        if (!imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $sourceWidth, $sourceHeight)) {
            imagedestroy($target);
            throw new RuntimeException('Gambar gagal diperkecil.');
        }
        return $target;
    }

    private static function canvas(int $width, int $height)
    {
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            throw new RuntimeException('Memori server tidak cukup untuk memproses gambar.');
        }
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);
        return $canvas;
    }

    private static function save($image, string $path, string $format, int $quality): void
    {
        $saved = $format === 'jpg'
            ? self::saveJpeg($image, $path, $quality)
            : imagewebp($image, $path, $quality);
        if (!$saved || !is_file($path) || filesize($path) < 1) {
            throw new RuntimeException('Salah satu ukuran gambar gagal disimpan.');
        }
    }

    private static function saveJpeg($image, string $path, int $quality): bool
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $background = imagecreatetruecolor($width, $height);
        if ($background === false) {
            return false;
        }
        $white = imagecolorallocate($background, 255, 255, 255);
        imagefill($background, 0, 0, $white);
        imagealphablending($background, true);
        imagecopy($background, $image, 0, 0, 0, 0, $width, $height);
        $saved = imagejpeg($background, $path, $quality);
        imagedestroy($background);
        return $saved;
    }

    private static function ensureDirectory(string $directory, int $permissions): void
    {
        if (!is_dir($directory) && !mkdir($directory, $permissions, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder upload tidak dapat dibuat.');
        }
    }

    private static function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
