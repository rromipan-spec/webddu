export function managedImageVariant(url, variant) {
    const value = String(url || '').trim();
    return value.replace(
        /(\/uploads\/[a-f0-9]{32})\/(?:thumb|card|content|hero|social)\.(?:webp|jpg)(\?.*)?$/i,
        `$1/${variant}.${variant === 'social' ? 'jpg' : 'webp'}$2`
    );
}
