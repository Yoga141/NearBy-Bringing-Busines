<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UmkmResource;
use App\Models\Submission;
use App\Models\Umkm;
use App\Support\UmkmCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        $umkms = $query->with(['items', 'photos'])->orderByDesc('rating')->orderBy('id')->get();

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

        // Count real visits only: not the owner or an admin checking the page,
        // and one visit per visitor per UMKM per window - refreshing the page
        // (or hammering the endpoint) must not inflate the number. The counter
        // is only ever written here; no endpoint or import can set it.
        if (! $isOwnerOrAdmin && Cache::add($this->viewKey($request, $umkm, $user), true, now()->addHours(self::VIEW_WINDOW_HOURS))) {
            // Query-builder increment: a view is not an edit, so `updated_at` stays.
            DB::table('umkms')->where('id', $umkm->id)->increment('views');
            $umkm->views++;
        }

        $umkm->load(['items', 'photos', 'reviews' => fn ($q) => $q->latest()]);

        if ($user) {
            $umkm->is_favorite = $user->favorites()->where('umkm_id', $umkm->id)->exists();
        }

        return new UmkmResource($umkm);
    }

    /**
     * Create a UMKM.
     *
     * An owner's UMKM waits in the verification queue; one an admin adds is
     * published straight away (the admin *is* the verifier) and has no owner
     * account until one is linked.
     */
    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $umkm = DB::transaction(function () use ($data, $user, $isAdmin) {
            $umkm = Umkm::create([
                ...collect($data)->except('items')->all(),
                'owner_id' => $isAdmin ? null : $user->id,
                'verification' => $isAdmin ? 'disetujui' : 'menunggu',
                'status' => $data['status'] ?? 'aktif',
            ]);

            if (! empty($data['items'])) {
                $this->syncItems($umkm, $data['items']);
            }

            if (! $isAdmin) {
                Submission::openFor($umkm, $user);
            }

            return $umkm;
        });

        return (new UmkmResource($umkm->load(['items', 'photos'])))->response()->setStatusCode(201);
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

        return new UmkmResource($umkm->load(['items', 'photos']));
    }

    /** Soft delete (moves to Trash). */
    public function destroy(Request $request, Umkm $umkm)
    {
        $this->authorizeOwner($request, $umkm);
        $umkm->delete();

        return response()->json(['message' => 'UMKM dipindahkan ke sampah.']);
    }

    /** Hours during which repeat visits by the same visitor count once. */
    private const VIEW_WINDOW_HOURS = 6;

    /** Signed-in visitors are keyed by account, guests by IP + browser. */
    private function viewKey(Request $request, Umkm $umkm, $user): string
    {
        $who = $user ? 'u'.$user->id : 'g'.sha1($request->ip().'|'.$request->userAgent());

        return "umkm-view:{$umkm->id}:{$who}";
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
            'name' => [$req, 'string', 'min:2', 'max:255'],
            'category' => [$req, Rule::in(UmkmCatalog::CATEGORIES)],
            'location' => [$req, Rule::in(UmkmCatalog::LOCATIONS)],
            'price_label' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:2000'],
            'img_label' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s.]{6,40}$/'],
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
            'name.min' => 'Nama usaha minimal 2 karakter.',
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, +, -, atau tanda kurung.',
            'status.in' => 'Status hanya boleh aktif, libur, atau tutup.',
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
