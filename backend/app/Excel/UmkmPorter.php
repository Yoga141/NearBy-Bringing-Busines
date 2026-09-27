<?php

namespace App\Excel;

use App\Models\Submission;
use App\Http\Controllers\Api\UmkmPhotoController;
use App\Models\Umkm;
use App\Models\User;
use App\Support\UmkmCatalog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Excel export / import of UMKM rows.
 *
 * Admins see and write every UMKM; an owner only their own, and a row an owner
 * adds always enters the verification queue - importing can never publish.
 */
class UmkmPorter extends Porter
{
    public function sheetName(): string
    {
        return 'UMKM';
    }

    public function exportPrefix(): string
    {
        return 'umkm-nearby';
    }

    public function templateFilename(): string
    {
        return 'template-impor-umkm.xlsx';
    }

    public function requiredColumn(): string
    {
        return 'name';
    }

    public function columns(): array
    {
        return [
            new Column('ID', 'id', 8, Column::NUMBER),
            new Column('Nama Usaha', 'name', 30, example: 'Warung Contoh Rasa'),
            new Column('Kategori', 'category', 16, example: 'Kuliner'),
            new Column('Wilayah', 'location', 20, example: 'Balikpapan Kota'),
            new Column('Alamat', 'address', 34, example: 'Jl. Contoh No. 1'),
            new Column('Jam Buka', 'hours', 20, example: '08.00 - 21.00 WITA'),
            new Column('Telepon', 'phone', 18, example: '0812-0000-0000'),
            new Column('Instagram', 'ig', 20, example: '@contoh.rasa'),
            new Column('Kisaran Harga', 'price_label', 16, example: 'Rp15-50rb'),
            new Column('Deskripsi Singkat', 'tag', 40, example: 'Masakan rumahan khas Balikpapan.'),
            new Column('Status', 'status', 12, Column::TITLE_CASE, example: 'Aktif'),
            new Column('Link Foto', 'photo_url', 40, example: 'https://contoh.com/foto-warung.jpg'),
            new Column('Verifikasi', 'verification', 14, Column::TITLE_CASE, adminOnly: true, example: 'Disetujui'),
            new Column('Email Pemilik', 'owner_email', 26, adminOnly: true, readOnly: true),
            new Column('Rating', 'rating', 10, readOnly: true),
            new Column('Jumlah Ulasan', 'reviews_count', 14, readOnly: true),
            new Column('Dilihat', 'views', 10, readOnly: true),
        ];
    }

    public function guide(bool $isAdmin): array
    {
        return array_values(array_filter([
            ...Guide::howTo('data', $this->sheetName()),
            'Kategori: '.implode(', ', UmkmCatalog::CATEGORIES),
            'Wilayah: '.implode(', ', UmkmCatalog::LOCATIONS),
            'Status: Aktif, Libur, Tutup',
            $isAdmin ? 'Verifikasi: Menunggu, Disetujui, Ditolak' : null,
            'Telepon: angka saja, boleh memakai spasi, +, - atau tanda kurung. Contoh: 0812-3456-7890.',
            '',
            'Wajib diisi untuk UMKM baru: Nama Usaha, Kategori, Wilayah.',
            'Nama Usaha + Wilayah tidak boleh kembar, baik di dalam file maupun dengan UMKM yang sudah ada.',
            'Untuk memperbarui UMKM yang sudah ada, isi kolom ID-nya - jangan menambah baris baru.',
            '',
            'Link Foto: alamat gambar yang sudah online (diawali https://), satu link per baris.',
            'File foto tidak bisa dimasukkan ke Excel. Untuk mengunggah foto dari komputer/HP,',
            'buka Dashboard -> Kelola & edit UMKM -> bagian Foto.',
            '',
            'Kolom Rating, Jumlah Ulasan, dan Dilihat hanya informasi - perubahannya diabaikan saat impor.',
            $isAdmin ? null : 'UMKM baru dari impor akan menunggu verifikasi admin sebelum tampil di website.',
        ], fn ($line) => $line !== null));
    }

    public function exportRows(User $user): array
    {
        $isAdmin = $user->isAdmin();

        $query = Umkm::query()
            ->select(['id', 'owner_id', 'name', 'category', 'location', 'address', 'hours', 'phone', 'ig',
                'price_label', 'tag', 'status', 'verification', 'rating', 'reviews_count', 'views'])
            ->with('photos')
            ->orderBy('id');

        if ($isAdmin) {
            $query->with('owner:id,email');
        } else {
            $query->where('owner_id', $user->id);
        }

        return $query->get()->map(fn (Umkm $u) => array_filter([
            'id' => $u->id,
            'name' => $u->name,
            'category' => $u->category,
            'location' => $u->location,
            'address' => $u->address,
            'hours' => $u->hours,
            'phone' => $u->phone,
            'ig' => $u->ig,
            'price_label' => $u->price_label,
            'tag' => $u->tag,
            'status' => $u->status,
            'photo_url' => $this->absoluteUrl($u->photos->first()?->public_url),
            // Admin-only columns; an owner's sheet simply doesn't carry them.
            'verification' => $isAdmin ? $u->verification : null,
            'owner_email' => $isAdmin ? $u->owner?->email : null,
            'rating' => (float) $u->rating,
            'reviews_count' => (int) $u->reviews_count,
            'views' => (int) $u->views,
        ], fn ($v) => $v !== null))->all();
    }

    protected function prepare(array $raws, User $user): array
    {
        $ids = self::idsIn($raws, 'id');
        $existing = $ids
            ? Umkm::whereIn('id', $ids)->get(['id', 'owner_id', 'name', 'location'])->keyBy('id')
            : collect();

        // Duplicate check on "name|location" of every row as it will be after
        // the import (a blank cell on an update keeps the stored value).
        $keys = [];
        foreach ($raws as $i => $raw) {
            $target = ($id = self::intOrNull($raw, 'id')) ? $existing->get($id) : null;
            $name = $raw['name'] ?? $target?->name;
            $location = $raw['location'] ?? $target?->location;
            if (is_string($name) && is_string($location)) {
                $keys[$i] = self::dupKey($name, $location);
            }
        }

        $names = array_filter(array_unique(array_map(
            fn ($raw) => is_string($raw['name'] ?? null) ? trim($raw['name']) : '',
            $raws,
        )));

        return [
            'existing' => $existing,
            'sheetCounts' => array_count_values($keys),
            // Stored UMKM (trash included - restoring one would collide) by name|location.
            'stored' => $names
                ? Umkm::withTrashed()->whereIn('name', array_values($names))->get(['id', 'name', 'location'])
                    ->mapWithKeys(fn (Umkm $u) => [self::dupKey($u->name, $u->location) => $u->id])
                : collect(),
        ];
    }

    private static function dupKey(string $name, string $location): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name)).'|'.$location;
    }

    protected function inspect(array $raw, array $context, User $user): array
    {
        $isAdmin = $user->isAdmin();
        $messages = [];

        $id = self::intOrNull($raw, 'id');
        $target = $id ? $context['existing']->get($id) : null;
        $isUpdate = $target !== null;

        if ($id !== null && ! $isUpdate) {
            $messages[] = "ID {$id} tidak ditemukan.";
        }
        if ($isUpdate && ! $isAdmin && $target->owner_id !== $user->id) {
            $messages[] = "ID {$id} bukan UMKM milikmu.";
        }

        $validator = Validator::make($raw, $this->rules($isUpdate, $isAdmin), $this->messages());
        if ($validator->fails()) {
            array_push($messages, ...$validator->errors()->all());
        }

        $name = $raw['name'] ?? $target?->name;
        $location = $raw['location'] ?? $target?->location;
        if (is_string($name) && is_string($location) && ! $validator->errors()->hasAny(['name', 'location'])) {
            $key = self::dupKey($name, $location);
            if (($context['sheetCounts'][$key] ?? 0) > 1) {
                $messages[] = "\"{$name}\" di {$location} muncul lebih dari sekali di file ini.";
            }
            $storedId = $context['stored'][$key] ?? null;
            if ($storedId !== null && $storedId !== $id) {
                $messages[] = "\"{$name}\" di {$location} sudah terdaftar (ID {$storedId}). Isi kolom ID dengan {$storedId} untuk memperbaruinya.";
            }
        }

        if ($messages) {
            return ['id' => $id, 'update' => $isUpdate, 'name' => (string) ($raw['name'] ?? ''), 'messages' => $messages, 'data' => []];
        }

        // Blank cells mean "leave as is" on update, "use the default" on create.
        $data = collect($validator->validated())
            ->only([...$this->writableKeys($isAdmin), 'photo_url'])
            ->reject(fn ($v) => $v === null || $v === '')
            ->all();

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

        $updateIds = array_values(array_filter(array_map(
            fn ($row) => $row['action'] === 'update' ? $row['id'] : null,
            $rows,
        )));
        $targets = $updateIds ? Umkm::whereIn('id', $updateIds)->get()->keyBy('id') : collect();

        foreach ($rows as $row) {
            $data = $row['data'];

            if ($row['action'] === 'update') {
                $umkm = $targets->get($row['id']);
                // Guard again inside the transaction: the row could have been
                // deleted or reassigned since it was analysed.
                if ($umkm && ($isAdmin || $umkm->owner_id === $user->id)) {
                    $umkm->update(collect($data)->except('photo_url')->all());
                    $this->attachPhotoUrl($umkm, $data['photo_url'] ?? null);
                }

                continue;
            }

            $umkm = Umkm::create([
                ...collect($data)->except('photo_url')->all(),
                'owner_id' => $user->id,
                // An owner can never publish by importing - new rows queue for
                // verification exactly like the normal "ajukan UMKM" flow.
                'verification' => $isAdmin ? ($data['verification'] ?? 'disetujui') : 'menunggu',
                'status' => $data['status'] ?? 'aktif',
            ]);

            // Same as UmkmController::store(): a non-admin's new row needs a
            // Submission, or it never reaches the approval queue.
            if (! $isAdmin) {
                Submission::openFor($umkm, $user);
            }

            $this->attachPhotoUrl($umkm, $data['photo_url'] ?? null);
        }
    }

    /**
     * Add an external photo link, unless the UMKM already has it (a sheet
     * exported from here and re-imported carries the current cover back).
     */
    private function attachPhotoUrl(Umkm $umkm, ?string $url): void
    {
        if (! $url) {
            return;
        }

        $photos = $umkm->photos()->get();
        foreach ($photos as $photo) {
            if ($photo->public_url === $url || $this->absoluteUrl($photo->public_url) === $url) {
                return;
            }
        }
        if ($photos->count() >= UmkmPhotoController::MAX_PHOTOS) {
            return;
        }

        $umkm->photos()->create([
            'url' => $url,
            'sort_order' => $photos->isEmpty() ? 0 : (int) $photos->max('sort_order') + 1,
        ]);
    }

    /** "/api/umkm-photos/x.jpg" -> "https://domain/api/umkm-photos/x.jpg", so an exported link works anywhere. */
    private function absoluteUrl(?string $url): ?string
    {
        if ($url === null || preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return url($url);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(bool $isUpdate, bool $isAdmin): array
    {
        // On update a blank cell means "leave as is" (it arrives as null and
        // is dropped afterwards), so only a new row demands the core fields.
        $req = $isUpdate ? 'nullable' : 'required';

        $rules = [
            'name' => [$req, 'string', 'max:255'],
            'category' => [$req, Rule::in(UmkmCatalog::CATEGORIES)],
            'location' => [$req, Rule::in(UmkmCatalog::LOCATIONS)],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s.]{6,40}$/'],
            'ig' => ['nullable', 'string', 'max:255'],
            'price_label' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(UmkmCatalog::STATUSES)],
            'photo_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
        ];

        if ($isAdmin) {
            $rules['verification'] = ['nullable', Rule::in(UmkmCatalog::VERIFICATIONS)];
        }

        return $rules;
    }

    /** @return list<string> */
    private function writableKeys(bool $isAdmin): array
    {
        $keys = ['name', 'category', 'location', 'address', 'hours', 'phone', 'ig', 'price_label', 'tag', 'status'];

        // Verification is an admin decision; rating/views are derived figures
        // and are never taken from a spreadsheet.
        return $isAdmin ? [...$keys, 'verification'] : $keys;
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'Kolom "Nama Usaha" wajib diisi.',
            'name.max' => 'Kolom "Nama Usaha" maksimal 255 karakter.',
            'category.required' => 'Kolom "Kategori" wajib diisi.',
            'category.in' => 'Kategori tidak dikenali. Pilih: '.implode(', ', UmkmCatalog::CATEGORIES).'.',
            'location.required' => 'Kolom "Wilayah" wajib diisi.',
            'location.in' => 'Wilayah tidak dikenali. Pilih: '.implode(', ', UmkmCatalog::LOCATIONS).'.',
            'status.in' => 'Status hanya boleh: Aktif, Libur, atau Tutup.',
            'verification.in' => 'Verifikasi hanya boleh: Menunggu, Disetujui, atau Ditolak.',
            'phone.max' => 'Kolom "Telepon" maksimal 40 karakter.',
            'phone.regex' => 'Kolom "Telepon" hanya boleh berisi angka, spasi, +, -, atau tanda kurung.',
            'name.string' => 'Kolom "Nama Usaha" harus berupa teks.',
            'photo_url.url' => 'Kolom "Link Foto" harus berupa alamat web yang diawali http:// atau https://.',
            'photo_url.max' => 'Kolom "Link Foto" terlalu panjang.',
            'tag.max' => 'Kolom "Deskripsi Singkat" maksimal 2000 karakter.',
        ];
    }
}
