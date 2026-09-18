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
use Illuminate\Support\Facades\DB;

#[Fillable([
    'owner_id', 'name', 'category', 'location', 'rating', 'reviews_count',
    'price_label', 'tag', 'img_label', 'address', 'hours', 'phone', 'ig',
    'list_label', 'status', 'verification', 'views', 'hidden',
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
     * Fold a review change into the cached `rating` / `reviews_count`.
     *
     * Incremental rather than recomputed from the `reviews` table on purpose:
     * the seeded figures (e.g. 4.8 from 213 reviews) are mock data with only a
     * handful of stored rows behind them, and a recount would throw them away.
     * The row is locked while it is read and rewritten, so two reviews posted
     * at the same moment can't overwrite each other's change.
     *
     * @param  int  $starsDelta  Stars added (negative when removed).
     * @param  int  $countDelta  +1 new review, -1 deleted review, 0 edited.
     */
    public function applyReviewDelta(int $starsDelta, int $countDelta): void
    {
        DB::transaction(function () use ($starsDelta, $countDelta) {
            $row = static::withTrashed()->whereKey($this->getKey())->lockForUpdate()
                ->first(['id', 'rating', 'reviews_count']);
            if (! $row) {
                return;
            }

            $count = max(0, $row->reviews_count + $countDelta);
            $rating = $count > 0
                ? min(5, max(0, round(($row->rating * $row->reviews_count + $starsDelta) / $count, 1)))
                : 0;

            // Query-builder update: a rating change is not an edit of the
            // UMKM, so `updated_at` is left alone.
            static::withTrashed()->toBase()->where('id', $row->id)
                ->update(['rating' => $rating, 'reviews_count' => $count]);

            $this->forceFill(['rating' => $rating, 'reviews_count' => $count])->syncOriginal();
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

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }
}
