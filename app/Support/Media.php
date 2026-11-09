<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns a stored image path (or a full web address used by the sample data) into a URL.
 */
class Media
{
    /**
     * @param  bool  $small  Use the small "-sm" WebP copy made on upload.
     */
    public static function url(?string $path, bool $small = false): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $small && Str::contains($path, 'images.unsplash.com')
                ? Str::replaceMatches('/([?&])w=\d+/', '${1}w=600', $path)
                : $path;
        }

        if ($small) {
            $smallPath = Str::replaceLast('.webp', '-sm.webp', $path);

            if ($smallPath !== $path && Storage::disk('public')->exists($smallPath)) {
                return Storage::disk('public')->url($smallPath);
            }
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Delete an uploaded image and its small copy.
     */
    public static function delete(?string $path): void
    {
        if (blank($path) || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        Storage::disk('public')->delete([$path, Str::replaceLast('.webp', '-sm.webp', $path)]);
    }

    public static function placeholder(): string
    {
        return asset('images/placeholder.svg');
    }
}
