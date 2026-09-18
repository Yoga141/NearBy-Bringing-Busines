<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'umkm_id', 'owner_id', 'name', 'owner_name', 'category',
    'location', 'status', 'checks', 'files',
])]
class Submission extends Model
{
    protected function casts(): array
    {
        return [
            'checks' => 'array',
            'files' => 'array',
        ];
    }

    /**
     * Queue a freshly created UMKM for admin verification.
     *
     * Every path that lets a non-admin create a UMKM (the "ajukan UMKM" form,
     * the Excel import) must go through here: without a Submission row the
     * UMKM never reaches the approval queue and stays invisible for good.
     */
    public static function openFor(Umkm $umkm, User $owner): self
    {
        return self::create([
            'umkm_id' => $umkm->id,
            'owner_id' => $owner->id,
            'name' => $umkm->name,
            'owner_name' => $owner->name,
            'category' => $umkm->category,
            'location' => $umkm->location,
            'status' => 'menunggu',
            'checks' => [],
            'files' => [],
        ]);
    }

    public function umkm(): BelongsTo
    {
        return $this->belongsTo(Umkm::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
