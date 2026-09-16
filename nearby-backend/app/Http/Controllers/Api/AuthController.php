<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Register a new account (role: user or owner). */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', Rule::in(['user', 'owner'])],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid, misalnya nama@email.com.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $data['role'] ?? 'user',
            'status' => ($data['role'] ?? 'user') === 'owner' ? 'menunggu' : 'aktif',
        ]);

        $token = $user->createToken($this->deviceName($request))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    /** Log in and return an API token. */
    public function login(Request $request)
    {
        // Messages are in Indonesian because the frontend shows `message` /
        // `errors` verbatim (see ApiError.firstError) and the rest of the UI
        // is Indonesian.
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid, misalnya nama@email.com.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $user = User::where('email', Str::lower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }

        // An account the admin switched off must not get a new token. Only
        // 'nonaktif' blocks sign-in: a freshly registered owner is 'menunggu'
        // until their UMKM is verified, and they are meant to be able to log
        // in while they wait.
        if ($user->status === 'nonaktif') {
            throw ValidationException::withMessages([
                'email' => ['Akun ini dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.'],
            ]);
        }

        $token = $user->createToken($this->deviceName($request))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    /**
     * Name a token after the device that asked for it.
     *
     * Sanctum stores no IP or user agent of its own, so the token name is the
     * only place to record which device a session belongs to — without it the
     * "Sesi aktif" list could only ever show anonymous rows.
     */
    private function deviceName(Request $request): string
    {
        return Str::limit($request->userAgent() ?: 'api', 255, '');
    }

    /** Revoke the current access token. */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /** Currently authenticated user. */
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }
}
