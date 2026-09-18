<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Hash compared against when the email is unknown, so a wrong email and a
     * wrong password take the same time and the response time can't be used
     * to find out which addresses have an account.
     */
    private const DUMMY_HASH = '$2y$12$PsBOwTf1ljjoeY5M/1wIJOmFc5TU2RkM8B0XsdkQV76.YzXmZaZgq';

    /** Register a new account (role: user or owner) and sign it in. */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'] ?? 'user';

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'role' => $role,
            // An owner is 'menunggu' until their first UMKM is approved; they
            // can still sign in and use the dashboard meanwhile.
            'status' => $role === 'owner' ? 'menunggu' : 'aktif',
        ]);

        return $this->issueToken($request, $user, 201);
    }

    /** Log in and return an API token. */
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::where('email', $data['email'])->first();

        if (! Hash::check($data['password'], $user?->password ?? self::DUMMY_HASH) || ! $user) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }

        // An account the admin switched off must not get a new token. Only
        // 'nonaktif' blocks sign-in: a freshly registered owner is 'menunggu'
        // until their UMKM is verified, and may log in while they wait.
        if ($user->status === 'nonaktif') {
            throw ValidationException::withMessages([
                'email' => ['Akun ini dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.'],
            ]);
        }

        // Upgrade the stored hash transparently if the cost factor changed.
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $data['password']])->save();
        }

        return $this->issueToken($request, $user);
    }

    /** Revoke the current access token. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    /** Currently authenticated user. */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function issueToken(Request $request, User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken($this->deviceName($request))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], $status);
    }

    /**
     * Name a token after the device that asked for it.
     *
     * Sanctum stores no IP or user agent of its own, so the token name is the
     * only place to record which device a session belongs to - without it the
     * "Sesi aktif" list could only ever show anonymous rows.
     */
    private function deviceName(Request $request): string
    {
        return Str::limit($request->userAgent() ?: 'api', 255, '');
    }
}
