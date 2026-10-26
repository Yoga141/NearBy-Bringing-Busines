<?php

namespace App\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Validating and storing uploaded photos (UMKM, product, profile) in a way
 * that also works when PHP's `fileinfo` extension is missing.
 *
 * Laravel's `image` / `mimetypes` rules and `UploadedFile::store()` all sniff
 * the MIME type through fileinfo; without it they throw ("Class finfo not
 * found", "no guessers are available"). Here the file's content is checked
 * with getimagesize() (part of core PHP, no fileinfo needed) and the stored
 * extension comes from that result, never from guessExtension(). When
 * fileinfo is available the normal Laravel rules run as well.
 */
final class ImageUpload
{
    /** Accepted by name (the fallback's extension check). */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    /** getimagesize() image type => extension the file is stored under. */
    private const STORED_EXTENSIONS = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    private const CONTENT_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public const MISSING_FILEINFO = 'Server belum mengaktifkan ekstensi PHP fileinfo. Hubungi admin server.';

    /**
     * Whether PHP's fileinfo extension can be used. UPLOAD_SIMULATE_NO_FILEINFO
     * makes it report false, to try the fallback on a machine that has it.
     */
    public static function fileinfoAvailable(): bool
    {
        return class_exists('finfo', false) && ! config('filesystems.simulate_missing_fileinfo');
    }

    /**
     * Call before touching an upload: without fileinfo and with the fallback
     * switched off (UPLOAD_FILEINFO_FALLBACK=false), stop with a message an
     * admin can act on instead of a raw PHP error.
     */
    public static function ensureSupported(): void
    {
        abort_if(
            ! self::fileinfoAvailable() && ! config('filesystems.fileinfo_fallback'),
            500,
            self::MISSING_FILEINFO
        );
    }

    /**
     * Validation rules for one photo field. `bail` so a file that fails the
     * type check reports that one problem, not three variants of it.
     *
     * @return array<int, mixed>
     */
    public static function rules(int $maxKilobytes): array
    {
        $rules = ['bail', 'required', 'file', 'max:'.$maxKilobytes];

        if (self::fileinfoAvailable()) {
            array_push($rules, 'image', 'mimetypes:'.implode(',', self::MIME_TYPES));
        } else {
            // Name only; the closure below checks what is really inside.
            $rules[] = 'extensions:'.implode(',', self::EXTENSIONS);
        }

        $rules[] = function (string $attribute, mixed $value, Closure $fail) {
            if ($value instanceof UploadedFile && self::storedExtension($value) === null) {
                $fail(self::label($attribute).' bukan gambar JPG, PNG, atau WebP yang valid (isi berkasnya bukan gambar).');
            }
        };

        return $rules;
    }

    /**
     * Store under a random 40-character name (like Laravel's hashName) with
     * the extension of the file's real content. False if the write failed.
     */
    public static function store(UploadedFile $file, string $directory, string $disk): string|false
    {
        $extension = self::storedExtension($file);
        abort_if($extension === null, 422, 'Berkas bukan gambar JPG, PNG, atau WebP yang valid.');

        return $file->storeAs($directory, Str::random(40).'.'.$extension, $disk);
    }

    /**
     * Content-Type for serving a stored photo, from its extension. Passed to
     * response()->file() so Symfony does not sniff it (which needs fileinfo).
     */
    public static function contentType(string $path): string
    {
        return self::CONTENT_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? 'application/octet-stream';
    }

    /** Extension matching the file's content, or null when it is not an accepted image. */
    private static function storedExtension(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        $info = $path ? @getimagesize($path) : false;

        return $info ? (self::STORED_EXTENSIONS[$info[2]] ?? null) : null;
    }

    /** "photos.2" => "Foto ke-3", "photo" => "Berkas". */
    private static function label(string $attribute): string
    {
        return preg_match('/\.(\d+)$/', $attribute, $m) ? 'Foto ke-'.((int) $m[1] + 1) : 'Berkas';
    }
}
