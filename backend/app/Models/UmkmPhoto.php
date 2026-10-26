<?php

namespace App\Models;

use App\Support\MediaUrl;
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
     * Uploaded files on the local `public` disk are served by the web server
     * straight from `/storage/{path}` - the stored path is the file's location
     * under storage/app/public, reached through the `storage` symlink in the
     * API's web root. Absolute on APP_URL (see MediaUrl), because the SPA runs
     * on another domain. Files on a cloud disk use the disk's own public URL.
     */
    protected function publicUrl(): Attribute
    {
        return Attribute::get(function (): ?string {
            if ($this->url) {
                return MediaUrl::absolute($this->url);
            }
            if (! $this->path) {
                return null;
            }
            if (config("filesystems.disks.{$this->disk}.driver") === 'local') {
                return MediaUrl::absolute('/storage/'.ltrim($this->path, '/'));
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
