<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    private const STATUS_LABELS = [
        'baru' => 'Baru',
        'dijawab' => 'Dijawab',
        'ditutup' => 'Ditutup',
    ];

    /**
     * Shape matches the frontend `Question` type.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'text' => $this->text,
            'name' => $this->name ?: ($this->user?->name ?? 'Anonim'),
            'contact' => $this->contact,
            'answer' => $this->answer,
            'status' => self::STATUS_LABELS[$this->status] ?? 'Baru',
            'when' => $this->created_at?->diffForHumans(),
        ];
    }
}
