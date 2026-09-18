<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A registration tutorial video hosted by us and shown on the Panduan page.
 */
#[Fillable(['title', 'description', 'disk', 'path', 'original_name', 'mime_type', 'size', 'active', 'uploaded_by'])]
class GuideVideo extends Model
{
    protected $casts = [
        'active' => 'boolean',
        'size' => 'integer',
    ];

    /** The video shown to visitors: newest active upload, or null if none. */
    public static function current(): ?self
    {
        return self::where('active', true)->latest('id')->first();
    }

    /**
     * Playback URL.
     *
     * The bytes are streamed back through the API rather than linked straight
     * into `storage/`, because the public disk is only reachable from the web
     * after `php artisan storage:link` - a symlink that quietly fails to exist
     * on plenty of Windows and shared-hosting setups. Going through a route
     * works the same everywhere, and keeps range requests (seeking) working.
     *
     * Deliberately relative: an absolute URL built from APP_URL would point at
     * the Laravel origin directly, sidestepping the Vite dev proxy and breaking
     * the moment APP_URL doesn't match the host actually serving the SPA.
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => "/api/guide-video/{$this->id}/file");
    }

    /** "12,4 MB" - ready to print, in Indonesian decimal notation. */
    protected function sizeLabel(): Attribute
    {
        return Attribute::get(function (): string {
            $mb = $this->size / 1024 / 1024;

            return $mb >= 1
                ? number_format($mb, 1, ',', '.').' MB'
                : number_format($this->size / 1024, 0, ',', '.').' KB';
        });
    }

    /** True when the uploaded file is actually still on disk. */
    public function fileExists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /** Absolute path for the streaming response. */
    public function absolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    /** Remove the row's file from disk; safe to call when it is already gone. */
    public function deleteFile(): void
    {
        Storage::disk($this->disk)->delete($this->path);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
