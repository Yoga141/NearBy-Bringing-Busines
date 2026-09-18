<?php

namespace App\Excel;

use App\Models\Submission;
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

        return [
            'existing' => $ids
                ? Umkm::whereIn('id', $ids)->get(['id', 'owner_id', 'name'])->keyBy('id')
                : collect(),
        ];
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

        if ($messages) {
            return ['id' => $id, 'update' => $isUpdate, 'name' => (string) ($raw['name'] ?? ''), 'messages' => $messages, 'data' => []];
        }

        // Blank cells mean "leave as is" on update, "use the default" on create.
        $data = collect($validator->validated())
            ->only($this->writableKeys($isAdmin))
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
                    $umkm->update($data);
                }

                continue;
            }

            $umkm = Umkm::create([
                ...$data,
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
        }
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
            'phone' => ['nullable', 'string', 'max:40'],
            'ig' => ['nullable', 'string', 'max:255'],
            'price_label' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(UmkmCatalog::STATUSES)],
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
            'tag.max' => 'Kolom "Deskripsi Singkat" maksimal 2000 karakter.',
        ];
    }
}
