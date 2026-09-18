<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The signed-in user's own account: profile fields, password, active sessions.
 *
 * The profile photo lives in its own controller because it deals in uploaded
 * files rather than JSON.
 */
class ProfileController extends Controller
{
    /** Update name / email / phone. */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Ignore this user's own row, or re-saving the form without
            // touching the email would collide with itself.
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:40'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid, misalnya nama@email.com.',
            'email.unique' => 'Email ini sudah dipakai akun lain.',
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'] ?? null,
        ]);

        return new UserResource($user->fresh());
    }

    /** Change the password, given the current one. */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi saat ini salah.'],
            ]);
        }

        $user->update(['password' => $data['password']]);

        // Anyone signed in elsewhere with the old password is signed out: that
        // is the whole point of changing it after a suspected compromise. The
        // token making this request survives, so the user stays logged in here.
        $user->tokens()->whereKeyNot($request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Kata sandi berhasil diperbarui.']);
    }

    /**
     * Devices currently signed in - one row per Sanctum token.
     *
     * The device label comes from the User-Agent recorded as the token's name
     * at login. Tokens carry no IP or location, so this reports what we
     * actually know (device and when it was last used) and nothing more.
     */
    public function sessions(Request $request)
    {
        $currentId = $request->user()->currentAccessToken()->id;

        return response()->json(
            $request->user()->tokens()->latest('last_used_at')->latest('id')->get()->map(fn ($token) => [
                'id' => $token->id,
                'device' => self::deviceLabel($token->name),
                'current' => $token->id === $currentId,
                'mobile' => self::isMobile($token->name),
                'lastUsed' => $token->last_used_at
                    ? $token->last_used_at->diffForHumans()
                    : 'Belum pernah dipakai',
                'createdAt' => $token->created_at?->translatedFormat('j M Y, H:i'),
            ]),
        );
    }

    /** Sign one other device out. */
    public function revokeSession(Request $request, int $id)
    {
        if ($id === $request->user()->currentAccessToken()->id) {
            throw ValidationException::withMessages([
                'session' => ['Tidak bisa mengeluarkan perangkat yang sedang kamu pakai. Gunakan tombol Keluar.'],
            ]);
        }

        $deleted = $request->user()->tokens()->whereKey($id)->delete();
        abort_if($deleted === 0, 404, 'Sesi tidak ditemukan.');

        return response()->noContent();
    }

    /**
     * "Chrome · Windows" from a User-Agent string.
     *
     * A deliberately small lookup rather than a UA-parsing package: this only
     * has to be recognisable to the person reading their own session list.
     */
    private static function deviceLabel(string $userAgent): string
    {
        if ($userAgent === '' || $userAgent === 'api') {
            return 'Perangkat tidak dikenal';
        }

        $browsers = ['Edg' => 'Edge', 'OPR' => 'Opera', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'];
        $systems = ['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iPhone', 'iPad' => 'iPad', 'Mac OS' => 'macOS', 'Linux' => 'Linux'];

        $browser = 'Peramban lain';
        foreach ($browsers as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                $browser = $label;
                break;
            }
        }

        $system = '';
        foreach ($systems as $needle => $label) {
            if (str_contains($userAgent, $needle)) {
                $system = $label;
                break;
            }
        }

        return $system ? "{$browser} · {$system}" : $browser;
    }

    private static function isMobile(string $userAgent): bool
    {
        return (bool) preg_match('/Android|iPhone|iPad|Mobile/i', $userAgent);
    }
}
