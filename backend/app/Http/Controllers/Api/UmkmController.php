<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UmkmResource;
use App\Models\Submission;
use App\Models\Umkm;
use App\Support\UmkmCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UmkmController extends Controller
{
    /** Public list with ?category= &location= &q= filters. */
    public function index(Request $request)
    {
        $query = Umkm::query()->visible();

        if (($cat = $request->query('category')) && $cat !== 'Semua') {
            $query->where('category', $cat);
        }

        if (($loc = $request->query('location')) && $loc !== 'Semua') {
            $query->where('location', $loc);
        }

        if (is_string($q = $request->query('q')) && ($q = trim($q)) !== '') {
            // Make "%" and "_" in a search literal. The escape character is
            // spelled out in the query because SQLite has no default one
            // (MySQL uses a backslash); "!" reads the same on both.
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_substr($q, 0, 100)).'%';
            $query->where(function ($sub) use ($like) {
                foreach (['name', 'tag', 'address'] as $column) {
                    $sub->orWhereRaw("{$column} like ? escape '!'", [$like]);
                }
            });
        }

        $umkms = $query->with('items')->orderByDesc('rating')->orderBy('id')->get();

        return UmkmResource::collection($umkms);
    }

    /** Detail with menu items and reviews. */
    public function show(Request $request, Umkm $umkm)
    {
        // This route is public, so the `auth:sanctum` middleware never runs
        // and `$request->user()` would always be null. Resolving the guard
        // explicitly lets an owner preview their pending UMKM, an admin open
        // any UMKM, and a signed-in visitor get their favourite flag.
        $user = $request->user('sanctum');
        $isOwnerOrAdmin = $user && ($user->isAdmin() || $umkm->owner_id === $user->id);
        abort_unless($umkm->isVisible() || $isOwnerOrAdmin, 404);

        // Count real visits only - not the owner checking their own page.
        // Plain query-builder increment: a view is not an edit, so
        // `updated_at` must not move.
        if (! $isOwnerOrAdmin) {
            DB::table('umkms')->where('id', $umkm->id)->increment('views');
            $umkm->views++;
        }

        $umkm->load(['items', 'reviews' => fn ($q) => $q->latest()]);

        if ($user) {
            $umkm->is_favorite = $user->favorites()->where('umkm_id', $umkm->id)->exists();
        }

        return new UmkmResource($umkm);
    }

    /** Owner submits a new UMKM (pending verification). */
    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $user = $request->user();

        $umkm = DB::transaction(function () use ($data, $user) {
            $umkm = Umkm::create([
                ...collect($data)->except('items')->all(),
                'owner_id' => $user->id,
                'verification' => 'menunggu',
                'status' => 'aktif',
            ]);

            if (! empty($data['items'])) {
                $this->syncItems($umkm, $data['items']);
            }

            Submission::openFor($umkm, $user);

            return $umkm;
        });

        return new UmkmResource($umkm->load('items'));
    }

    /** Owner edits their own UMKM. */
    public function update(Request $request, Umkm $umkm)
    {
        $this->authorizeOwner($request, $umkm);

        $data = $this->validateData($request, partial: true);

        DB::transaction(function () use ($umkm, $data) {
            $umkm->update(collect($data)->except('items')->all());

            if (array_key_exists('items', $data)) {
                $this->syncItems($umkm, $data['items']);
            }
        });

        return new UmkmResource($umkm->load('items'));
    }

    /** Soft delete (moves to Trash). */
    public function destroy(Request $request, Umkm $umkm)
    {
        $this->authorizeOwner($request, $umkm);
        $umkm->delete();

        return response()->json(['message' => 'UMKM dipindahkan ke sampah.']);
    }

    private function authorizeOwner(Request $request, Umkm $umkm): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $umkm->owner_id === $user->id, 403, 'Bukan pemilik UMKM ini.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$req, 'string', 'max:255'],
            'category' => [$req, Rule::in(UmkmCatalog::CATEGORIES)],
            'location' => [$req, Rule::in(UmkmCatalog::LOCATIONS)],
            'price_label' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:2000'],
            'img_label' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'ig' => ['nullable', 'string', 'max:255'],
            'list_label' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(UmkmCatalog::STATUSES)],
            'items' => ['sometimes', 'array', 'max:200'],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.price' => ['nullable', 'string', 'max:255'],
            'items.*.img' => ['nullable', 'string', 'max:255'],
            'items.*.available' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Nama usaha wajib diisi.',
            'category.required' => 'Kategori wajib dipilih.',
            'category.in' => 'Kategori tidak dikenali.',
            'location.required' => 'Wilayah wajib dipilih.',
            'location.in' => 'Wilayah tidak dikenali.',
            'items.*.name.required_with' => 'Setiap produk wajib punya nama.',
        ]);
    }

    /**
     * Replace the UMKM's products with the given list, in one bulk insert.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Umkm $umkm, array $items): void
    {
        $umkm->items()->delete();

        $now = now();
        $rows = array_map(fn (array $item) => [
            'umkm_id' => $umkm->id,
            'name' => $item['name'],
            'price' => $item['price'] ?? null,
            'img' => $item['img'] ?? null,
            'available' => (bool) ($item['available'] ?? true),
            'created_at' => $now,
            'updated_at' => $now,
        ], array_values($items));

        if ($rows) {
            $umkm->items()->insert($rows);
        }
    }
}
