<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ReviewResource extends JsonResource
{
    /**
     * Shape matches the frontend `Review` type.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Only fall back to the user relation when it is already loaded, so a
        // list of reviews never triggers one extra query per row.
        $name = $this->author_name
            ?? ($this->relationLoaded('user') ? $this->user?->name : null)
            ?? 'Pengguna';

        return [
            'id' => (string) $this->id,
            'umkmId' => $this->umkm_id,
            'umkmName' => $this->whenLoaded('umkm', fn () => $this->umkm->name),
            'umkmCat' => $this->whenLoaded('umkm', fn () => $this->umkm->category),
            'userId' => $this->user_id,
            'initial' => Str::upper(Str::substr(trim($name), 0, 1)),
            'name' => $name,
            'stars' => (int) $this->stars,
            'date' => $this->created_at?->diffForHumans(),
            'text' => $this->text,
            'reply' => $this->reply,
        ];
    }
}
