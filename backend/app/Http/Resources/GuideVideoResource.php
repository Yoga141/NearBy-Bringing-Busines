<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuideVideoResource extends JsonResource
{
    /**
     * Shape matches the frontend `GuideVideo` type.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'mimeType' => $this->mime_type,
            'size' => $this->size,
            'sizeLabel' => $this->size_label,
            'originalName' => $this->original_name,
            'active' => $this->active,
            // The dashboard warns about rows whose file vanished (a manual
            // cleanup of storage/, a half-restored backup) instead of leaving
            // the guide page pointing at a 404.
            'fileMissing' => ! $this->fileExists(),
            'uploadedBy' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'uploadedAt' => $this->created_at?->translatedFormat('j M Y, H:i'),
        ];
    }
}
