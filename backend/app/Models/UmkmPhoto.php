<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One photo of a UMKM: an uploaded file on a storage disk, or an external
 * link (from the Excel import). See the `umkm_photos` migration.
 */
#[Fillable(['umkm_id', 'disk', 'path', 'url', 'sort_order'])]
class UmkmPhoto extends Model
{
    /** Folder on the disk that holds uploaded UMKM photos. */
    public const DIRECTORY = 'umkm-photos';

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * The disk new uploads go to. `public` (storage/app/public) by default -
     * persistent on the cPanel host, since deploys only copy files over it.
     * Set MEDIA_DISK=s3 on a host whose filesystem is wiped on deploy.
     */
    public static function uploadDisk(): string
    {
        return (string) config('filesystems.media_disk', 'public');
    }

    /**
     * What an <img> should load.
     *
     * Uploaded files on a local disk are served through `GET /api/umkm-photos/{file}`
     * (same reasoning as avatars: no `storage:link` symlink needed, and the API
     * lives at the same origin as the SPA). Files on a cloud disk use the disk's
     * own public URL.
     */
    protected function publicUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->url) {
                return $this->url;
            }
            if (! $this->path) {
                return null;
            }
            if (config("filesystems.disks.{$this->disk}.driver") === 'local') {
                return '/api/umkm-photos/'.basename($this->path);
            }

            return Storage::disk($this->disk)->url($this->path);
        });
    }

    /** Remove the uploaded file, if this row has one. */
    public function deleteFile(): void
    {
        if ($this->path && $this->disk) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }
}
