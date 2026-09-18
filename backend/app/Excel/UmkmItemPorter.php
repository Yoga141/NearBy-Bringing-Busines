<?php

namespace App\Excel;

use App\Models\Umkm;
use App\Models\UmkmItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Excel export / import of UMKM products (menu items).
 *
 * A product only exists inside a UMKM, so every row carries the parent's id.
 * That parent is ownership-checked in both phases: an owner may only ever
 * write products into a UMKM that is theirs.
 */
class UmkmItemPorter extends Porter
{
    public function sheetName(): string
    {
        return 'Produk';
    }

    public function exportPrefix(): string
    {
        return 'produk-nearby';
    }

    public function templateFilename(): string
    {
        return 'template-impor-produk.xlsx';
    }

    public function requiredColumn(): string
    {
        return 'name';
    }

    public function columns(): array
    {
        return [
            new Column('ID', 'id', 8, Column::NUMBER),
            new Column('ID UMKM', 'umkm_id', 10, Column::NUMBER, example: 1),
            new Column('Nama Usaha', 'umkm_name', 30, readOnly: true),
            new Column('Nama Produk', 'name', 30, example: 'Nasi Kuning Spesial'),
            new Column('Harga', 'price', 16, example: 'Rp20.000'),
            new Column('Link Gambar', 'img', 34),
            new Column('Tersedia', 'available', 12, Column::BOOLEAN, example: 'Ya'),
        ];
    }

    public function guide(bool $isAdmin): array
    {
        return [
            ...Guide::howTo('produk', $this->sheetName()),
            'Kolom "ID UMKM" wajib diisi untuk produk baru - ambil angkanya dari kolom ID',
            'pada hasil "Unduh Excel" di kartu Export & Import UMKM.',
            '',
            'Tersedia: Ya atau Tidak (kosong dianggap Ya untuk produk baru).',
            'Harga boleh ditulis bebas, misalnya "Rp20.000" atau "20rb".',
            '',
            'Kolom Nama Usaha hanya informasi - perubahannya diabaikan saat impor.',
            'Untuk memindahkan produk ke UMKM lain, ubah kolom ID UMKM-nya.',
        ];
    }

    public function exportRows(User $user): array
    {
        $query = UmkmItem::query()
            ->with('umkm:id,owner_id,name')
            ->orderBy('umkm_id')
            ->orderBy('id');

        if (! $user->isAdmin()) {
            $query->whereHas('umkm', fn ($q) => $q->where('owner_id', $user->id));
        }

        return $query->get()->map(fn (UmkmItem $item) => [
            'id' => $item->id,
            'umkm_id' => $item->umkm_id,
            // Context only - the sheet matches on ID UMKM, never on this name.
            'umkm_name' => $item->umkm?->name,
            'name' => $item->name,
            'price' => $item->price,
            'img' => $item->img,
            'available' => (bool) $item->available,
        ])->all();
    }

    protected function prepare(array $raws, User $user): array
    {
        $itemIds = self::idsIn($raws, 'id');
        $umkmIds = self::idsIn($raws, 'umkm_id');

        return [
            'items' => $itemIds
                ? UmkmItem::with('umkm:id,owner_id')->whereIn('id', $itemIds)->get()->keyBy('id')
                : collect(),
            'umkms' => $umkmIds
                ? Umkm::whereIn('id', $umkmIds)->get(['id', 'owner_id', 'name'])->keyBy('id')
                : collect(),
        ];
    }

    protected function inspect(array $raw, array $context, User $user): array
    {
        $isAdmin = $user->isAdmin();
        $messages = [];

        $id = self::intOrNull($raw, 'id');
        $target = $id ? $context['items']->get($id) : null;
        $isUpdate = $target !== null;

        if ($id !== null && ! $isUpdate) {
            $messages[] = "ID produk {$id} tidak ditemukan.";
        }
        if ($isUpdate && ! $isAdmin && $target->umkm?->owner_id !== $user->id) {
            $messages[] = "ID produk {$id} bukan milik UMKM-mu.";
        }

        // The parent is optional on update ("leave it where it is") but must
        // exist and be writable whenever the sheet does name one.
        $umkmId = self::intOrNull($raw, 'umkm_id');
        if ($umkmId !== null) {
            $parent = $context['umkms']->get($umkmId);
            if (! $parent) {
                $messages[] = "ID UMKM {$umkmId} tidak ditemukan.";
            } elseif (! $isAdmin && $parent->owner_id !== $user->id) {
                $messages[] = "ID UMKM {$umkmId} bukan UMKM milikmu.";
            }
        }

        $validator = Validator::make($raw, $this->rules($isUpdate), $this->messages());
        if ($validator->fails()) {
            array_push($messages, ...$validator->errors()->all());
        }

        if ($messages) {
            return ['id' => $id, 'update' => $isUpdate, 'name' => (string) ($raw['name'] ?? ''), 'messages' => $messages, 'data' => []];
        }

        $data = collect($validator->validated())
            ->only(['umkm_id', 'name', 'price', 'img', 'available'])
            // `available` is legitimately false, so only blanks are dropped.
            ->reject(fn ($v) => $v === null || $v === '')
            ->all();

        if (isset($data['umkm_id'])) {
            $data['umkm_id'] = (int) $data['umkm_id'];
        }
        if (array_key_exists('available', $data)) {
            $data['available'] = filter_var($data['available'], FILTER_VALIDATE_BOOLEAN);
        }

        return [
            'id' => $id,
            'update' => $isUpdate,
            'name' => $data['name'] ?? ($target->name ?? ''),
            'messages' => [],
            'data' => $data,
        ];
    }

    protected function apply(array $rows, User $user): void
    {
        $isAdmin = $user->isAdmin();

        $updateIds = [];
        $parentIds = [];
        foreach ($rows as $row) {
            if ($row['action'] === 'update') {
                $updateIds[] = $row['id'];
            }
            if (isset($row['data']['umkm_id'])) {
                $parentIds[] = $row['data']['umkm_id'];
            }
        }

        // Re-read inside the transaction - the product or its parent could
        // have changed since the rows were analysed. Two queries in total.
        $items = $updateIds
            ? UmkmItem::with('umkm:id,owner_id')->whereIn('id', $updateIds)->get()->keyBy('id')
            : collect();
        $parents = $parentIds
            ? Umkm::whereIn('id', array_unique($parentIds))->get(['id', 'owner_id'])->keyBy('id')
            : collect();

        foreach ($rows as $row) {
            $data = $row['data'];
            $destination = $data['umkm_id'] ?? null;

            if ($row['action'] === 'update') {
                $item = $items->get($row['id']);
                if (! $item || (! $isAdmin && $item->umkm?->owner_id !== $user->id)) {
                    continue;
                }
                // Moving a product to another UMKM is allowed, but the
                // destination is re-checked here, not trusted from the preview.
                if ($destination !== null && ! $this->mayWriteTo($parents, $destination, $user)) {
                    continue;
                }
                $item->update($data);

                continue;
            }

            if ($destination !== null && $this->mayWriteTo($parents, $destination, $user)) {
                UmkmItem::create([...$data, 'available' => $data['available'] ?? true]);
            }
        }
    }

    /** Whether the user may add or move a product into the given UMKM. */
    private function mayWriteTo(Collection $parents, int $umkmId, User $user): bool
    {
        $umkm = $parents->get($umkmId);

        return $umkm !== null && ($user->isAdmin() || $umkm->owner_id === $user->id);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(bool $isUpdate): array
    {
        // On update a blank cell means "leave as is" (it arrives as null and
        // is dropped afterwards), so only a new row demands the parent and
        // the product name.
        $req = $isUpdate ? 'nullable' : 'required';

        return [
            'umkm_id' => [$req, 'integer'],
            'name' => [$req, 'string', 'max:255'],
            'price' => ['nullable', 'string', 'max:255'],
            'img' => ['nullable', 'string', 'max:255'],
            'available' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'umkm_id.required' => 'Kolom "ID UMKM" wajib diisi untuk produk baru.',
            'umkm_id.integer' => 'Kolom "ID UMKM" harus berupa angka.',
            'name.required' => 'Kolom "Nama Produk" wajib diisi.',
            'name.max' => 'Kolom "Nama Produk" maksimal 255 karakter.',
            'available.boolean' => 'Kolom "Tersedia" hanya boleh diisi Ya atau Tidak.',
        ];
    }
}
