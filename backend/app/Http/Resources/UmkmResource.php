<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UmkmResource extends JsonResource
{
    /**
     * Shape matches the frontend `Umkm` type (cat/loc/reviews naming).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ownerId' => $this->owner_id,
            'ownerName' => $this->whenLoaded('owner', fn () => $this->owner?->name),
            'ownerEmail' => $this->when(
                $this->relationLoaded('owner') && $request->user()?->isAdmin(),
                fn () => $this->owner?->email,
            ),
            'name' => $this->name,
            'cat' => $this->category,
            'loc' => $this->location,
            'rating' => (float) $this->rating,
            'reviews' => (int) $this->reviews_count,
            'priceLabel' => $this->price_label,
            'tag' => $this->tag,
            'imgLabel' => $this->img_label,
            'address' => $this->address,
            'hours' => $this->hours,
            'phone' => $this->phone,
            'ig' => $this->ig,
            'listLabel' => $this->list_label,
            'status' => $this->status,
            'verification' => $this->verification,
            'hidden' => (bool) $this->hidden,
            'views' => (int) $this->views,
            'items' => UmkmItemResource::collection($this->whenLoaded('items')),
            'photos' => $this->whenLoaded('photos', fn () => $this->photos->map(fn ($photo) => [
                'id' => $photo->id,
                'url' => $photo->public_url,
                'external' => $photo->url !== null,
            ])->values()),
            'coverUrl' => $this->whenLoaded('photos', fn () => $this->photos->first()?->public_url),
            'reviewsList' => ReviewResource::collection($this->whenLoaded('reviews')),
            'isFavorite' => $this->when(isset($this->is_favorite), fn () => (bool) $this->is_favorite),
            'deletedAt' => $this->deleted_at?->diffForHumans(),
        ];
    }
}
