<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Umkm;
use App\Models\UmkmItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Product/menu photos. Publicly readable; uploads and replacement are admin-only via api.php. */
class ProductPhotoController extends Controller
{
    private const DIRECTORY = 'product-photos';
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_KILOBYTES = 4096;

    /** Public image route; <img> tags cannot attach the user's Bearer token. */
    public function show(string $filename): BinaryFileResponse
    {
        $path = self::DIRECTORY.'/'.basename($filename);
        abort_unless(Storage::disk('public')->exists($path), 404, 'Foto produk tidak ditemukan.');

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    /** Upload or replace one product photo. Accessible to UMKM owner and admin. */
    public function store(Request $request, Umkm $umkm, UmkmItem $item)
    {
        $this->assertCanManage($request, $umkm, $item);

        $request->validate([
            'photo' => [
                'required', 'file', 'image',
                'mimetypes:'.implode(',', self::ALLOWED_MIMES),
                'max:'.self::MAX_KILOBYTES,
            ],
        ], [
            'photo.required' => 'Pilih foto produk terlebih dahulu.',
            'photo.image' => 'Berkas harus berupa gambar.',
            'photo.mimetypes' => 'Format foto harus JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto produk maksimal 4 MB.',
            'photo.uploaded' => 'PHP menolak berkas. Periksa upload_max_filesize dan post_max_size di php.ini.',
        ]);

        $newPath = $request->file('photo')->store(self::DIRECTORY, 'public');
        abort_unless($newPath, 500, 'Foto gagal disimpan. Periksa izin folder storage.');

        $oldPath = $this->managedPath($item->img);
        $url = '/api/product-photos/'.basename($newPath);

        try {
            $item->update(['img' => $url]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($newPath);
            throw $e;
        }

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(['id' => $item->id, 'img' => $url]);
    }

    /** Remove a product photo. Accessible to UMKM owner and admin. */
    public function destroy(Request $request, Umkm $umkm, UmkmItem $item)
    {
        $this->assertCanManage($request, $umkm, $item);
        $oldPath = $this->managedPath($item->img);
        $item->update(['img' => null]);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return response()->json(['id' => $item->id, 'img' => null]);
    }

    private function assertCanManage(Request $request, Umkm $umkm, UmkmItem $item): void
    {
        abort_unless((int) $item->umkm_id === (int) $umkm->id, 404, 'Produk tidak ditemukan pada UMKM ini.');
        $user = $request->user();
        abort_unless($user && ($user->isAdmin() || (int) $umkm->owner_id === (int) $user->id), 403, 'Bukan pemilik UMKM ini.');
    }

    /** Only delete a file whose URL belongs to this app's product-photo route. */
    private function managedPath(?string $url): ?string
    {
        if (! $url || ! preg_match('~(?:^|/)api/product-photos/([A-Za-z0-9_-]+\.(?:jpg|jpeg|png|webp))$~i', $url, $matches)) {
            return null;
        }

        return self::DIRECTORY.'/'.$matches[1];
    }
}
