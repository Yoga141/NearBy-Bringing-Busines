<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

// `rating`, `reviews_count` and `views` are deliberately NOT fillable: they
// are derived from real reviews and real visits (see refreshRating() and
// UmkmController::show()), so no request or spreadsheet can set them.
#[Fillable([
    'owner_id', 'name', 'category', 'location',
    'price_label', 'tag', 'img_label', 'address', 'hours', 'phone', 'ig',
    'list_label', 'status', 'verification', 'hidden',
])]
class Umkm extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'reviews_count' => 'integer',
            'views' => 'integer',
            'hidden' => 'boolean',
        ];
    }

    /** Approved and not hidden by an admin - what the public site may show. */
    #[Scope]
    protected function visible(Builder $query): void
    {
        $query->where('verification', 'disetujui')->where('hidden', false);
    }

    public function isVisible(): bool
    {
        return $this->verification === 'disetujui' && ! $this->hidden;
    }

    /**
     * Recompute the cached `rating` / `reviews_count` from the reviews table.
     *
     * Always a full recount rather than an incremental update, so the figure
     * shown on cards can never drift from the reviews that actually exist.
     * Query-builder update: a new rating is not an edit of the UMKM, so
     * `updated_at` is left alone.
     */
    public function refreshRating(): void
    {
        $stats = Review::where('umkm_id', $this->getKey())
            ->selectRaw('count(*) as total, avg(stars) as average')
            ->first();

        $count = (int) ($stats->total ?? 0);
        $rating = $count > 0 ? round((float) $stats->average, 1) : 0;

        static::withTrashed()->toBase()->where('id', $this->getKey())
            ->update(['rating' => $rating, 'reviews_count' => $count]);

        $this->forceFill(['rating' => $rating, 'reviews_count' => $count])->syncOriginal();
    }

    /** Uploaded files go with the UMKM when it is deleted for good (rows cascade). */
    protected static function booted(): void
    {
        static::forceDeleting(function (Umkm $umkm) {
            $umkm->photos()->get()->each->deleteFile();
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UmkmItem::class);
    }

    /** Photos, cover first. */
    public function photos(): HasMany
    {
        return $this->hasMany(UmkmPhoto::class)->orderBy('sort_order')->orderBy('id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }
}
