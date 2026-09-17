<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialVideoResource extends JsonResource
{
    private const PLATFORM_LABELS = [
        'youtube' => 'YouTube',
        'instagram' => 'Instagram',
    ];

    /**
     * Shape matches the frontend `SocialVideo` type.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'platformLabel' => self::PLATFORM_LABELS[$this->platform] ?? $this->platform,
            'title' => $this->title,
            'url' => $this->url,
            'embedUrl' => $this->embed_url,
            'thumbnailUrl' => $this->thumbnail_url,
            'sortOrder' => $this->sort_order,
            'active' => $this->active,
        ];
    }
}
