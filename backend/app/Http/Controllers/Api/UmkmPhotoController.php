<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UmkmResource;
use App\Models\Umkm;
use App\Models\UmkmPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * UMKM photos: uploaded by the owner (or an admin), shown on cards and the
 * detail page.
 *
 * Upload flow: file → `umkm-photos/` on the media disk → a `umkm_photos` row
 * holding the path → `photos[].url` in every UmkmResource → <img src>.
 */
class UmkmPhotoController extends Controller
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Per file, in kilobytes. */
    private const MAX_KILOBYTES = 4096;

    /** Photos one UMKM may have in total. */
    public const MAX_PHOTOS = 8;

    /**
     * Public: serve one uploaded photo by its stored filename.
     *
     * Unauthenticated because an <img> cannot send the Bearer token; the
     * 40-character random filename Laravel assigns is what keeps it unguessable.
     */
    public function show(string $filename): BinaryFileResponse
    {
        $photo = UmkmPhoto::where('path', UmkmPhoto::DIRECTORY.'/'.basename($filename))->first();
        abort_unless($photo && $photo->disk, 404, 'Foto tidak ditemukan.');

        $disk = Storage::disk($photo->disk);
        abort_unless($disk->exists($photo->path), 404, 'Foto tidak ditemukan.');

        return response()->file($disk->path($photo->path), [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /** Owner/admin: add one or more photos (multipart `photos[]`). */
    public function store(Request $request, Umkm $umkm)
    {
        $this->authorizeOwner($request, $umkm);

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:'.self::MAX_PHOTOS],
            'photos.*' => [
                'required',
                'file',
                'image',
                'mimetypes:'.implode(',', self::ALLOWED_MIMES),
                'max:'.self::MAX_KILOBYTES,
            ],
        ], [
            'photos.required' => 'Pilih minimal satu foto.',
            'photos.max' => 'Maksimal '.self::MAX_PHOTOS.' foto sekaligus.',
            'photos.*.image' => 'Berkas harus berupa gambar.',
            'photos.*.mimetypes' => 'Format foto harus JPG, PNG, atau WebP.',
            'photos.*.max' => 'Ukuran tiap foto maksimal '.(self::MAX_KILOBYTES / 1024).' MB.',
            'photos.*.uploaded' => 'Foto ditolak server karena melebihi upload_max_filesize ('
                .ini_get('upload_max_filesize').'). Kecilkan fotonya, atau naikkan batas di php.ini.',
        ]);

        $files = $request->file('photos');
        $existing = $umkm->photos()->count();
        abort_if(
            $existing + count($files) > self::MAX_PHOTOS,
            422,
            'Satu UMKM maksimal '.self::MAX_PHOTOS.' foto. Hapus foto lama terlebih dahulu.'
        );

        $disk = UmkmPhoto::uploadDisk();
        $next = (int) $umkm->photos()->max('sort_order') + ($existing ? 1 : 0);
        $stored = [];

        try {
            DB::transaction(function () use ($files, $disk, $umkm, &$next, &$stored) {
                foreach ($files as $file) {
                    $path = $file->store(UmkmPhoto::DIRECTORY, $disk);
                    abort_unless($path, 500, 'Foto gagal disimpan di server. Periksa izin folder storage.');
                    $stored[] = $path;

                    $umkm->photos()->create([
                        'disk' => $disk,
                        'path' => $path,
                        'sort_order' => $next++,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            // Don't leave files on disk that no row points at.
            Storage::disk($disk)->delete($stored);
            throw $e;
        }

        return new UmkmResource($umkm->load(['items', 'photos']));
    }

    /** Owner/admin: make one photo the cover (first). */
    public function cover(Request $request, Umkm $umkm, UmkmPhoto $photo)
    {
        $this->authorizeOwner($request, $umkm);
        abort_unless($photo->umkm_id === $umkm->id, 404);

        DB::transaction(function () use ($umkm, $photo) {
            $order = 1;
            foreach ($umkm->photos()->get() as $p) {
                $p->update(['sort_order' => $p->id === $photo->id ? 0 : $order++]);
            }
        });

        return new UmkmResource($umkm->load(['items', 'photos']));
    }

    /** Owner/admin: remove one photo and its file. */
    public function destroy(Request $request, Umkm $umkm, UmkmPhoto $photo)
    {
        $this->authorizeOwner($request, $umkm);
        abort_unless($photo->umkm_id === $umkm->id, 404);

        $photo->deleteFile();
        $photo->delete();

        return new UmkmResource($umkm->load(['items', 'photos']));
    }

    private function authorizeOwner(Request $request, Umkm $umkm): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $umkm->owner_id === $user->id, 403, 'Bukan pemilik UMKM ini.');
    }
}
