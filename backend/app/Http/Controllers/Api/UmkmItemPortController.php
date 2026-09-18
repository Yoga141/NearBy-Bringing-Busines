<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Models\UmkmItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Bulk export / import of UMKM products (menu items) for the Excel feature.
 *
 * Mirrors {@see UmkmPortController}: the spreadsheet is written and parsed in
 * the browser (exceljs), this controller only speaks JSON rows, and import is
 * two-phase - `preview` reports without touching the database, `commit`
 * re-validates from scratch and applies inside a transaction.
 *
 * A product only exists inside a UMKM, so every row carries the parent's id.
 * That parent is ownership-checked in both phases: an owner may only ever
 * write products into a UMKM that is theirs.
 */
class UmkmItemPortController extends Controller
{
    /**
     * Rows for the export sheet: every product for an admin, only those under
     * the caller's own UMKM for an owner.
     */
    public function export(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->role === 'admin';

        $query = UmkmItem::query()->with('umkm:id,owner_id,name')->orderBy('umkm_id')->orderBy('id');
        if (! $isAdmin) {
            $query->whereHas('umkm', fn ($q) => $q->where('owner_id', $user->id));
        }

        $rows = $query->get()->map(fn (UmkmItem $item) => [
            'id' => $item->id,
            'umkm_id' => $item->umkm_id,
            // Context only - the sheet matches on ID UMKM, never on this name.
            'umkm_name' => $item->umkm?->name,
            'name' => $item->name,
            'price' => $item->price,
            'img' => $item->img,
            'available' => (bool) $item->available,
        ]);

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
                    $item = UmkmItem::with('umkm:id,owner_id')->find($row['id']);
                    // Guard again inside the transaction: the product or its
                    // parent could have changed between preview and commit.
                    if (! $item || (! $isAdmin && $item->umkm?->owner_id !== $user->id)) {
                        continue;
                    }
                    // Moving a product to another UMKM is allowed, but the
                    // destination is re-checked here, not trusted from preview.
                    if (isset($data['umkm_id']) && ! $this->mayWriteTo($data['umkm_id'], $user, $isAdmin)) {
                        continue;
                    }
                    $item->update($data);

                    continue;
                }

                if (! $this->mayWriteTo($data['umkm_id'] ?? null, $user, $isAdmin)) {
                    continue;
                }

                UmkmItem::create([
                    ...$data,
                    'available' => $data['available'] ?? true,
                ]);
            }
        });

        return response()->json([
            'message' => 'Impor selesai.',
            'summary' => $result['summary'],
        ]);
    }

    /** Whether the caller may add or move a product into the given UMKM. */
    private function mayWriteTo(?int $umkmId, User $user, bool $isAdmin): bool
    {
        if (! $umkmId) {
            return false;
        }

        $umkm = Umkm::find($umkmId);

        return $umkm !== null && ($isAdmin || $umkm->owner_id === $user->id);
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
        $raws = $request->input('rows');

        // One query each for the products and the parents the sheet names,
        // instead of a lookup per row.
        $itemIds = collect($raws)->pluck('id')
            ->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v)->unique();
        $items = $itemIds->isEmpty()
            ? collect()
            : UmkmItem::with('umkm:id,owner_id')->whereIn('id', $itemIds)->get()->keyBy('id');

        $umkmIds = collect($raws)->pluck('umkm_id')
            ->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v)->unique();
        $umkms = $umkmIds->isEmpty()
            ? collect()
            : Umkm::whereIn('id', $umkmIds)->get(['id', 'owner_id', 'name'])->keyBy('id');

        $rows = [];
        $summary = ['create' => 0, 'update' => 0, 'error' => 0];

        foreach ($raws as $i => $raw) {
            // Row 1 is the header in the sheet, so data starts at 2.
            $sheetRow = $i + 2;
            $raw = is_array($raw) ? $raw : [];
            $messages = [];

            $id = isset($raw['id']) && is_numeric($raw['id']) ? (int) $raw['id'] : null;
            $target = $id ? $items->get($id) : null;
            $isUpdate = $target !== null;

            if ($id !== null && ! $isUpdate) {
                $messages[] = "ID produk {$id} tidak ditemukan.";
            }

            if ($isUpdate && ! $isAdmin && $target->umkm?->owner_id !== $user->id) {
                $messages[] = "ID produk {$id} bukan milik UMKM-mu.";
            }

            // The parent is optional on update ("leave it where it is") but must
            // exist and be writable whenever the sheet does name one.
            $umkmId = isset($raw['umkm_id']) && is_numeric($raw['umkm_id']) ? (int) $raw['umkm_id'] : null;
            if ($umkmId !== null) {
                $parent = $umkms->get($umkmId);
                if (! $parent) {
                    $messages[] = "ID UMKM {$umkmId} tidak ditemukan.";
                } elseif (! $isAdmin && $parent->owner_id !== $user->id) {
                    $messages[] = "ID UMKM {$umkmId} bukan UMKM milikmu.";
                }
            }

            $validator = Validator::make($raw, $this->rules($isUpdate), $this->messages());
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
    private function rules(bool $isUpdate): array
    {
        // On update the sheet may leave a cell blank to mean "leave as is",
        // so only a new row demands the parent and the product name.
        $req = $isUpdate ? 'sometimes' : 'required';

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
            'available.boolean' => 'Kolom "Tersedia" hanya boleh diisi Ya atau Tidak.',
        ];
    }
}
