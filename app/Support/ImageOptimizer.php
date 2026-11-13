<?php

namespace App\Support;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turns an uploaded photo into two compressed WebP files on the public disk:
 * a large copy for the detail page and a small copy for cards and thumbnails.
 * Uses PHP's built-in GD extension so it runs on shared hosting.
 */
class ImageOptimizer
{
    public const int LARGE_WIDTH = 1600;

    public const int SMALL_WIDTH = 800;

    public const int LOGO_WIDTH = 600;

    public const int QUALITY = 80;

    /**
     * Store the upload as WebP and return the path of the large copy.
     *
     * The small copy is saved next to it with a "-sm" suffix.
     */
    public function store(UploadedFile $file, string $directory = 'products'): string
    {
        $source = $this->load($file);

        $path = trim($directory, '/').'/'.Str::lower((string) Str::ulid()).'.webp';

        Storage::disk('public')->put($path, $this->encode($source, self::LARGE_WIDTH));
        Storage::disk('public')->put(Str::replaceLast('.webp', '-sm.webp', $path), $this->encode($source, self::SMALL_WIDTH));

        return $path;
    }

    /**
     * Store a logo as a single WebP file (transparency kept) and return its path.
     */
    /**
     * Download a photo from the web and store it like an upload (large + small WebP).
     */
    public function storeFromUrl(string $url, string $directory = 'products'): string
    {
        $response = Http::timeout(60)->retry(2, 1000)->get($url);
        $temp = tempnam(sys_get_temp_dir(), 'img');

        if ($temp === false) {
            throw new RuntimeException('Could not create a temporary file.');
        }

        try {
            file_put_contents($temp, $response->body());

            return $this->store(new UploadedFile($temp, 'photo.jpg', null, null, true), $directory);
        } finally {
            @unlink($temp);
        }
    }

    public function storeLogo(UploadedFile $file): string
    {
        $path = 'branding/logo-'.Str::lower((string) Str::ulid()).'.webp';

        Storage::disk('public')->put($path, $this->encode($this->load($file), self::LOGO_WIDTH));

        return $path;
    }

    /**
     * A 12-megapixel phone photo needs about 50 MB once opened, more than the usual 128 MB limit
     * allows alongside the resized copies. Raise the limit for this request if the host lets us.
     */
    private function allowEnoughMemory(): void
    {
        $limit = ini_get('memory_limit');
        $bytes = $limit === '-1' ? PHP_INT_MAX : (int) $limit * match (strtoupper(substr(trim($limit), -1))) {
            'G' => 1024 ** 3,
            'M' => 1024 ** 2,
            'K' => 1024,
            default => 1,
        };

        if ($bytes < 384 * 1024 ** 2) {
            @ini_set('memory_limit', '384M');
        }
    }

    /**
     * Read the upload into a GD image, turning phone photos the right way up.
     */
    private function load(UploadedFile $file): GdImage
    {
        $this->allowEnoughMemory();

        $contents = (string) file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);

        if (! $image instanceof GdImage) {
            throw new RuntimeException('The file could not be read as an image.');
        }

        if (function_exists('exif_read_data') && in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            $exif = @exif_read_data($file->getRealPath());
            $orientation = is_array($exif) ? ($exif['Orientation'] ?? 1) : 1;

            $rotated = match ($orientation) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => $image,
            };

            if ($rotated instanceof GdImage) {
                $image = $rotated;
            }
        }

        return $image;
    }

    /**
     * Scale the image down to the given width (never up) and encode it as WebP.
     *
     * @param  positive-int  $maxWidth
     */
    private function encode(GdImage $source, int $maxWidth): string
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $image = $source;

        if ($width > $maxWidth) {
            $newHeight = max(1, (int) round($height * $maxWidth / $width));
            $image = imagecreatetruecolor($maxWidth, $newHeight);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagecopyresampled($image, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        } elseif (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        imagesavealpha($image, true);

        ob_start();
        imagewebp($image, null, self::QUALITY);

        return (string) ob_get_clean();
    }
}
