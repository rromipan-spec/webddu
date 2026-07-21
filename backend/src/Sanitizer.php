<?php
declare(strict_types=1);

final class Sanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'blockquote', 'a', 'div', 'figure', 'figcaption', 'img'];

    public static function richText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $dom->getElementById('root');
        if (!$root) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return '';
        }

        self::cleanChildren($root);
        $result = '';
        foreach ($root->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return $result;
    }

    private static function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    self::cleanChildren($node);
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    continue;
                }

                if ($tag === 'div' && $node->getAttribute('class') !== 'content-photo-grid') {
                    self::cleanChildren($node);
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                    continue;
                }

                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $name = strtolower($attribute->name);
                    $allowedAttribute = ($tag === 'a' && in_array($name, ['href', 'title'], true))
                        || ($tag === 'img' && in_array($name, ['src', 'alt', 'loading'], true))
                        || ($tag === 'div' && $name === 'class');
                    if (!$allowedAttribute) {
                        $node->removeAttribute($attribute->name);
                    }
                }
                if ($tag === 'a') {
                    $href = trim($node->getAttribute('href'));
                    if ($href !== '' && !preg_match('~^(https?://|/|#)~i', $href)) {
                        $node->removeAttribute('href');
                    }
                    $node->setAttribute('rel', 'noopener noreferrer');
                }

                if ($tag === 'img') {
                    $src = trim($node->getAttribute('src'));
                    if ($src === '' || (!preg_match('~^https?://~i', $src) && !str_starts_with($src, '/uploads/'))) {
                        $parent->removeChild($node);
                        continue;
                    }
                    $node->setAttribute('alt', mb_substr(trim($node->getAttribute('alt')), 0, 180));
                    $node->setAttribute('loading', 'lazy');
                }
                self::cleanChildren($node);
            }
        }
    }
}
