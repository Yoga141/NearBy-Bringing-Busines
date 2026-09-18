<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Profile photos: uploaded by the signed-in user, shown wherever their avatar
 * appears.
 */
class ProfilePhotoController extends Controller
{
    private const ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    /** Largest photo we accept, in kilobytes. */
    private const MAX_KILOBYTES = 2048;

    /**
     * Public: serve one photo by its stored filename.
     *
     * Unauthenticated on purpose - an <img> tag cannot send the Bearer token,
     * so a protected URL simply would not render. What stands in for auth is
     * the filename: Laravel's 40-character random upload name, which cannot be
     * guessed and, unlike a `/users/{id}/avatar` route, lets nobody walk the
     * user table to see who has a photo.
     */
    public function show(string $filename): BinaryFileResponse
    {
        // basename() again even though the route already constrains the
        // pattern: defence in depth against anything resolving outside the
        // avatars directory.
        $path = 'avatars/'.basename($filename);

        abort_unless(Storage::disk('public')->exists($path), 404, 'Foto tidak ditemukan.');

        return response()->file(Storage::disk('public')->path($path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** Upload or replace the signed-in user's profile photo. */
    public function store(Request $request)
    {
        $request->validate([
            'photo' => [
                'required',
                'file',
                'image',
                'mimetypes:'.implode(',', self::ALLOWED_MIMES),
                'max:'.self::MAX_KILOBYTES,
            ],
        ], [
            'photo.required' => 'Pilih berkas foto terlebih dahulu.',
            'photo.image' => 'Berkas harus berupa gambar.',
            'photo.mimetypes' => 'Format foto harus JPG, PNG, atau WebP.',
            'photo.max' => 'Ukuran foto melebihi batas '.(self::MAX_KILOBYTES / 1024).' MB.',
            // A file between `upload_max_filesize` and `post_max_size` arrives
            // flagged UPLOAD_ERR_INI_SIZE, whose stock message ("The photo
            // failed to upload") points nowhere near the php.ini line at fault.
            'photo.uploaded' => 'Berkas ditolak PHP karena melebihi upload_max_filesize ('
                .ini_get('upload_max_filesize').'). Kecilkan fotonya, atau naikkan '
                .'upload_max_filesize dan post_max_size di php.ini.',
        ]);

        $user = $request->user();

        // Replace, don't accumulate: without this every re-upload would leave
        // the previous file orphaned on disk forever.
        $user->deleteAvatarFile();

        $user->update([
            'avatar_path' => $request->file('photo')->store('avatars', 'public'),
        ]);

        return new UserResource($user->fresh());
    }

    /** Remove the photo and fall back to the initial avatar. */
    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->deleteAvatarFile();
        $user->update(['avatar_path' => null]);

        return new UserResource($user->fresh());
    }
}
