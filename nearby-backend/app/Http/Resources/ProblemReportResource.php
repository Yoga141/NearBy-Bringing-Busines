<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProblemReportResource extends JsonResource
{
    private const STATUS_LABELS = [
        'baru' => 'Baru',
        'ditinjau' => 'Ditinjau',
        'selesai' => 'Selesai',
    ];

    /**
     * Shape matches the frontend `ProblemReport` type.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'kind' => $this->kind,
            'text' => $this->text,
            'name' => $this->name ?: ($this->user?->name ?? 'Anonim'),
            'status' => self::STATUS_LABELS[$this->status] ?? 'Baru',
            'when' => $this->created_at?->diffForHumans(),
        ];
    }
}
