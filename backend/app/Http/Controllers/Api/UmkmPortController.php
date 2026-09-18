<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Models\Umkm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Bulk export / import of UMKM rows for the Excel feature in the dashboard.
 *
 * The spreadsheet itself is written and parsed in the browser (SheetJS); this
 * controller only speaks JSON rows. Import is deliberately two-phase -
 * `preview` validates and reports without touching the database, `commit`
 * re-validates from scratch and applies inside a transaction - so a bad sheet
 * can never half-apply, and the preview can't be tampered with to skip checks.
 */
class UmkmPortController extends Controller
{
    private const CATEGORIES = ['Kuliner', 'Penginapan', 'Fashion', 'Oleh-Oleh', 'Jasa'];

    private const LOCATIONS = [
        'Balikpapan Kota', 'Balikpapan Utara', 'Balikpapan Selatan',
        'Balikpapan Timur', 'Balikpapan Barat', 'Balikpapan Tengah',
    ];

    private const STATUSES = ['aktif', 'libur', 'tutup'];

    private const VERIFICATIONS = ['menunggu', 'disetujui', 'ditolak'];

    /**
     * Rows for the export sheet: every UMKM for an admin, only their own for
     * an owner. Read-only figures (rating, ulasan, dilihat) travel along for
     * context and are ignored on the way back in.
     */
    public function export(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        $query = Umkm::query()->with('owner:id,name,email')->orderBy('id');
        if (! $isAdmin) {
            $query->where('owner_id', $user->id);
        }

        $rows = $query->get()->map(fn (Umkm $u) => array_filter([
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
        ], fn ($v) => $v !== null));

        return response()->json([
            'isAdmin' => $isAdmin,
            'rows' => $rows,
        ]);
    }

    /** Validate an uploaded sheet and report what would happen, writing nothing. */
    public function preview(Request $request)
    {
        return response()->json($this->analyse($request));
    }

    /** Re-validate and apply. Refuses the whole sheet if any row is invalid. */
    public function commit(Request $request)
    {
        $result = $this->analyse($request);

        if ($result['summary']['error'] > 0) {
            return response()->json([
                'message' => 'Masih ada baris yang bermasalah. Perbaiki dulu lalu impor ulang.',
                ...$result,
            ], 422);
        }

        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        DB::transaction(function () use ($result, $user, $isAdmin) {
            foreach ($result['rows'] as $row) {
                $data = $row['data'];

                if ($row['action'] === 'update') {
                    $umkm = Umkm::find($row['id']);
                    // Guard again inside the transaction: the row could have been
                    // deleted or reassigned between preview and commit.
                    if (! $umkm || (! $isAdmin && $umkm->owner_id !== $user->id)) {
                        continue;
                    }
                    $umkm->update($data);

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

                // Same as UmkmController::store(): a non-admin's new row still
                // needs a Submission row, or it can never reach the approval
                // queue and stays permanently invisible.
                if (! $isAdmin) {
                    Submission::create([
                        'umkm_id' => $umkm->id,
                        'owner_id' => $user->id,
                        'name' => $umkm->name,
                        'owner_name' => $user->name,
                        'category' => $umkm->category,
                        'location' => $umkm->location,
                        'status' => 'menunggu',
                        'checks' => [],
                        'files' => [],
                    ]);
                }
            }
        });

        return response()->json([
            'message' => 'Impor selesai.',
            'summary' => $result['summary'],
        ]);
    }

    /**
     * Shared validation for both phases.
     *
     * @return array{summary: array<string, int>, rows: array<int, array<string, mixed>>}
     */
    private function analyse(Request $request): array
    {
        $request->validate([
            'rows' => ['required', 'array', 'min:1', 'max:2000'],
        ]);

        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        // One query for every id referenced by the sheet, instead of per row.
        $ids = collect($request->input('rows'))
            ->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique();
        $existing = $ids->isEmpty()
            ? collect()
            : Umkm::whereIn('id', $ids)->get(['id', 'owner_id', 'name'])->keyBy('id');

        $rows = [];
        $summary = ['create' => 0, 'update' => 0, 'error' => 0];

        foreach ($request->input('rows') as $i => $raw) {
            // Row 1 is the header in the sheet, so data starts at 2.
            $sheetRow = $i + 2;
            $raw = is_array($raw) ? $raw : [];
            $messages = [];

            $id = isset($raw['id']) && is_numeric($raw['id']) ? (int) $raw['id'] : null;
            $target = $id ? $existing->get($id) : null;
            $isUpdate = $target !== null;

            if ($id !== null && ! $isUpdate) {
                $messages[] = "ID {$id} tidak ditemukan.";
            }

            if ($isUpdate && ! $isAdmin && $target->owner_id !== $user->id) {
                $messages[] = "ID {$id} bukan UMKM milikmu.";
            }

            $validator = Validator::make($raw, $this->rules($isUpdate, $isAdmin), $this->messages());
            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $m) {
                    $messages[] = $m;
                }
            }

            if ($messages) {
                $summary['error']++;
                $rows[] = [
                    'row' => $sheetRow,
                    'action' => 'error',
                    'name' => (string) ($raw['name'] ?? ''),
                    'messages' => $messages,
                ];

                continue;
            }

            $data = collect($validator->validated())
                ->only($this->writableKeys($isAdmin))
                ->reject(fn ($v) => $v === null || $v === '')
                ->all();

            $action = $isUpdate ? 'update' : 'create';
            $summary[$action]++;
            $rows[] = [
                'row' => $sheetRow,
                'action' => $action,
                'id' => $id,
                'name' => $data['name'] ?? ($target->name ?? ''),
                'messages' => [],
                'data' => $data,
            ];
        }

        return ['summary' => $summary, 'rows' => $rows];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(bool $isUpdate, bool $isAdmin): array
    {
        // On update the sheet may legitimately leave a cell blank to mean
        // "leave as is", so only a new row demands the core fields.
        $req = $isUpdate ? 'sometimes' : 'required';

        $rules = [
            'name' => [$req, 'string', 'max:255'],
            'category' => [$req, Rule::in(self::CATEGORIES)],
            'location' => [$req, Rule::in(self::LOCATIONS)],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'ig' => ['nullable', 'string', 'max:255'],
            'price_label' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(self::STATUSES)],
        ];

        if ($isAdmin) {
            $rules['verification'] = ['nullable', Rule::in(self::VERIFICATIONS)];
        }

        return $rules;
    }

    /** @return array<int, string> */
    private function writableKeys(bool $isAdmin): array
    {
        $keys = [
            'name', 'category', 'location', 'address', 'hours',
            'phone', 'ig', 'price_label', 'tag', 'status',
        ];

        // Verification is an admin decision; rating/views are derived figures
        // and are never taken from a spreadsheet.
        return $isAdmin ? [...$keys, 'verification'] : $keys;
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'Kolom "Nama Usaha" wajib diisi.',
            'category.required' => 'Kolom "Kategori" wajib diisi.',
            'category.in' => 'Kategori tidak dikenali. Pilih: ' . implode(', ', self::CATEGORIES) . '.',
            'location.required' => 'Kolom "Wilayah" wajib diisi.',
            'location.in' => 'Wilayah tidak dikenali. Pilih: ' . implode(', ', self::LOCATIONS) . '.',
            'status.in' => 'Status hanya boleh: Aktif, Libur, atau Tutup.',
            'verification.in' => 'Verifikasi hanya boleh: Menunggu, Disetujui, atau Ditolak.',
        ];
    }
}
