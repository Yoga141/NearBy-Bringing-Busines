<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['umkm_id', 'name', 'price', 'img', 'available'])]
class UmkmItem extends Model
{
    /** Folder on the `public` disk that holds uploaded product photos. */
    public const PHOTO_DIRECTORY = 'product-photos';

    /** Storage path of a product photo URL this app manages, or null (icons, external links). */
    public static function managedPhotoPath(?string $url): ?string
    {
        if (! $url || ! preg_match('~(?:^|/)api/product-photos/([A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp))$~i', $url, $matches)) {
            return null;
        }

        return self::PHOTO_DIRECTORY.'/'.$matches[1];
    }

    /**
     * Delete the files behind these photo URLs once no product points at them
     * any more, so replaced or removed photos don't pile up on the host.
     *
     * @param  iterable<string|null>  $urls
     * @param  int|null  $ignoreUmkmId  treat this UMKM's rows as already gone
     */
    public static function deleteUnusedPhotos(iterable $urls, ?int $ignoreUmkmId = null): void
    {
        foreach (collect($urls)->filter()->unique() as $url) {
            $path = self::managedPhotoPath($url);
            if (! $path) {
                continue;
            }

            $stillUsed = static::query()
                ->where('img', $url)
                ->when($ignoreUmkmId, fn ($q) => $q->where('umkm_id', '!=', $ignoreUmkmId))
                ->exists();

            if (! $stillUsed) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    protected function casts(): array
    {
        return [
            'available' => 'boolean',
        ];
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }
}
