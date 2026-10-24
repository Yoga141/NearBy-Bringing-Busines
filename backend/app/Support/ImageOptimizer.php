<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shrinks uploaded photos: downscale to a max edge and re-encode as WebP.
 *
 * Every step is best-effort. Without GD/WebP, on an image GD cannot read, or
 * when the WebP would not be smaller, the original file is kept as-is, so an
 * upload never fails because of optimisation.
 */
class ImageOptimizer
{
    /** Larger images would need too much memory to decode on shared hosting. */
    private const MAX_SOURCE_PIXELS = 40_000_000;

    private const QUALITY = 80;

    /** Store an upload under `$directory`, optimised when possible. Returns the path or false. */
    public static function store(UploadedFile $file, string $directory, string $disk, int $maxEdge): string|false
    {
        $webp = self::toWebp($file->getRealPath(), $maxEdge, $file->getSize());
        if ($webp === null) {
            return $file->store($directory, $disk);
        }

        $path = $directory.'/'.Str::random(40).'.webp';

        return Storage::disk($disk)->put($path, $webp) ? $path : false;
    }

    /**
     * WebP bytes for the image at `$source`, or null when that would not help.
     *
     * @param  int|null  $originalBytes  skip the result unless it is smaller than this
     */
    public static function toWebp(string $source, int $maxEdge, ?int $originalBytes = null): ?string
    {
        if (! function_exists('imagewebp') || ! is_file($source)) {
            return null;
        }

        $info = @getimagesize($source);
        if (! $info || $info[0] * $info[1] > self::MAX_SOURCE_PIXELS) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            default => false,
        };
        if (! $image) {
            return null;
        }

        // GdImage objects free themselves (imagedestroy() is deprecated in PHP 8.5).
        if ($info[2] === IMAGETYPE_JPEG) {
            $image = self::applyExifOrientation($image, $source);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxEdge / max($width, $height));

        if ($scale < 1) {
            $resized = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), IMG_BICUBIC);
            if ($resized) {
                $image = $resized;
            }
        }

        // Keep PNG/WebP transparency.
        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $ok = imagewebp($image, null, self::QUALITY);
        $bytes = ob_get_clean();

        if (! $ok || ! $bytes) {
            return null;
        }

        return $originalBytes !== null && strlen($bytes) >= $originalBytes ? null : $bytes;
    }

    /** Phone cameras store rotation in EXIF; GD ignores it, so bake it into the pixels. */
    private static function applyExifOrientation(\GdImage $image, string $source): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($source)['Orientation'] ?? 1);
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };
        if ($angle === 0) {
            return $image;
        }

        return imagerotate($image, $angle, 0) ?: $image;
    }
}
