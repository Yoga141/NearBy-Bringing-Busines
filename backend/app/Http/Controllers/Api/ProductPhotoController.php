<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Models\UmkmItem;
use App\Support\ImageUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Product/menu photos. Publicly readable; the UMKM owner or an admin uploads and replaces them. */
class ProductPhotoController extends Controller
{
    private const DIRECTORY = UmkmItem::PHOTO_DIRECTORY;

    private const MAX_KILOBYTES = 2048;

    /** Public image route; <img> tags cannot attach the user's Bearer token. */
    public function show(string $filename): BinaryFileResponse
    {
        $path = self::DIRECTORY.'/'.basename($filename);
        abort_unless(Storage::disk('public')->exists($path), 404, 'Foto produk tidak ditemukan.');

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => ImageUpload::contentType($path),
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /** Upload or replace one product photo. Accessible to UMKM owner and admin. */
    public function store(Request $request, Umkm $umkm, UmkmItem $item)
    {
        $this->assertCanManage($request, $umkm, $item);
        ImageUpload::ensureSupported();

        $request->validate([
            'photo' => ImageUpload::rules(self::MAX_KILOBYTES),
        ], [
            'photo.required' => 'Pilih foto produk terlebih dahulu.',
            'photo.image' => 'Berkas harus berupa gambar.',
            'photo.mimetypes' => 'Format foto harus JPG, PNG, atau WebP.',
            'photo.extensions' => 'Format foto harus JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto produk maksimal '.(self::MAX_KILOBYTES / 1024).' MB.',
            'photo.uploaded' => 'PHP menolak berkas. Periksa upload_max_filesize dan post_max_size di php.ini.',
        ]);

        $newPath = ImageUpload::store($request->file('photo'), self::DIRECTORY, 'public');
        abort_unless($newPath, 500, 'Foto gagal disimpan. Periksa izin folder storage.');

        $oldUrl = $item->img;
        $url = '/api/product-photos/'.basename($newPath);

        try {
            $item->update(['img' => $url]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($newPath);
            throw $e;
        }

        UmkmItem::deleteUnusedPhotos([$oldUrl]);

        return response()->json(['id' => $item->id, 'img' => $url]);
    }

    /** Remove a product photo. Accessible to UMKM owner and admin. */
    public function destroy(Request $request, Umkm $umkm, UmkmItem $item)
    {
        $this->assertCanManage($request, $umkm, $item);
        $oldUrl = $item->img;
        $item->update(['img' => null]);
        UmkmItem::deleteUnusedPhotos([$oldUrl]);

        return response()->json(['id' => $item->id, 'img' => null]);
    }

    private function assertCanManage(Request $request, Umkm $umkm, UmkmItem $item): void
    {
        abort_unless((int) $item->umkm_id === (int) $umkm->id, 404, 'Produk tidak ditemukan pada UMKM ini.');
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || (int) $umkm->owner_id === (int) $user->id), 403, 'Bukan pemilik UMKM ini.');
    }
}
