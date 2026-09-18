<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /** Roles a visitor may pick for themselves - never "admin". */
    public const SELF_SERVICE_ROLES = ['user', 'owner'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalise before validating, so the `unique` check compares the same
     * lowercase form that is stored (SQLite compares case-sensitively, which
     * would otherwise let "Budi@Mail.com" register next to "budi@mail.com").
     */
    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : null,
            'email' => is_string($this->input('email')) ? Str::lower(trim($this->input('email'))) : null,
            'phone' => is_string($this->input('phone')) ? (trim($this->input('phone')) ?: null) : null,
        ], fn ($v) => $v !== null));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Soft-deleted accounts still hold their address (see Trash), so
            // the check deliberately includes them.
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['nullable', Rule::in(self::SELF_SERVICE_ROLES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid, misalnya nama@email.com.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'phone.max' => 'Nomor telepon maksimal 40 karakter.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'role.in' => 'Pilih peran Pengguna atau Pemilik UMKM.',
        ];
    }
}
