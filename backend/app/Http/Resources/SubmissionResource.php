<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmissionResource extends JsonResource
{
    /**
     * Shape matches the frontend `SubmissionRaw` type.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'umkmId' => $this->umkm_id,
            'name' => $this->name,
            'owner' => $this->owner_name ?? $this->owner?->name ?? 'Belum diisi',
            'cat' => $this->category,
            'loc' => $this->location,
            'status' => $this->status,
            'date' => $this->created_at?->translatedFormat('j M Y'),
            // Read from the UMKM as it is now, so the admin judges the real data.
            'checks' => $this->umkm ? [
                ['Deskripsi usaha', filled($this->umkm->tag)],
                ['Alamat lengkap', filled($this->umkm->address)],
                ['Nomor telepon', filled($this->umkm->phone)],
                ['Foto (min. 1)', $this->umkm->photos->isNotEmpty()],
                ['Jam operasional', filled($this->umkm->hours)],
                ['Produk / layanan', $this->umkm->items->isNotEmpty()],
            ] : ($this->checks ?? []),
            'files' => $this->umkm
                ? $this->umkm->photos->values()->map(fn ($photo, $i) => [
                    'name' => 'Foto '.($i + 1),
                    'kind' => 'image',
                    'ok' => true,
                    'meta' => $photo->url ? 'Link eksternal' : 'Diunggah',
                    'url' => $photo->public_url,
                ])->all()
                : ($this->files ?? []),
        ];
    }
}
