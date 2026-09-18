<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts for trying every role right away.
 *
 *   php artisan db:seed --class=UserSeeder     (accounts only)
 *   php artisan migrate:fresh --seed            (everything)
 *
 * Safe to run repeatedly: accounts are matched by email and brought back to
 * the documented state (password, role, active status) instead of failing on
 * the unique email. A soft-deleted demo account is restored.
 *
 * Passwords can be overridden from .env (SEED_*_PASSWORD) so a shared or
 * production environment never ends up with the public defaults below. env()
 * is safe here only because seeders run from the CLI; request-time code must
 * go through config() instead.
 */
class UserSeeder extends Seeder
{
    /** Emails other seeders use to look the demo owners up (never by id). */
    public const OWNER_EMAIL = 'pemilik@nearby.id';

    public const SECOND_OWNER_EMAIL = 'pemilik2@nearby.id';

    public function run(): void
    {
        $rows = [];

        foreach ($this->accounts() as $account) {
            $user = User::withTrashed()->firstOrNew(['email' => $account['email']]);
            $user->fill([
                'name' => $account['name'],
                'phone' => $account['phone'],
                'role' => $account['role'],
                'status' => 'aktif',
                'password' => $account['password'],
            ]);
            $user->deleted_at = null;
            $user->save();

            $rows[] = [$account['label'], $account['email'], $account['password'], $account['note']];
        }

        $this->command?->newLine();
        $this->command?->info('Akun demo siap dipakai untuk login:');
        $this->command?->table(['Peran', 'Email', 'Password', 'Keterangan'], $rows);
    }

    /**
     * @return list<array{label: string, name: string, email: string, phone: string, role: string, password: string, note: string}>
     */
    private function accounts(): array
    {
        // `?:` instead of env()'s default: a key that is present but blank
        // (`SEED_ADMIN_PASSWORD=`) returns "" and would seed an empty password.
        $admin = env('SEED_ADMIN_PASSWORD') ?: 'admin12345';
        $owner = env('SEED_OWNER_PASSWORD') ?: 'pemilik12345';
        $user = env('SEED_USER_PASSWORD') ?: 'pengguna12345';

        return [
            [
                'label' => 'Admin', 'name' => 'Admin NearBy', 'email' => 'admin@nearby.id',
                'phone' => '0812-0000-0001', 'role' => 'admin', 'password' => $admin,
                'note' => 'Dashboard admin: verifikasi, pengguna, laporan',
            ],
            [
                'label' => 'Pemilik', 'name' => 'Dewi Anjani', 'email' => self::OWNER_EMAIL,
                'phone' => '0812-0000-0002', 'role' => 'owner', 'password' => $owner,
                'note' => 'Pemilik "Warung Kepiting Kenari" & "Amplang Bahari"',
            ],
            [
                'label' => 'Pemilik', 'name' => 'Budi Santoso', 'email' => self::SECOND_OWNER_EMAIL,
                'phone' => '0812-0000-0003', 'role' => 'owner', 'password' => $owner,
                'note' => 'Pemilik "Kopi Saluang" & "Nasi Kuning Sambal Raja"',
            ],
            [
                'label' => 'Pengguna', 'name' => 'Jeki', 'email' => 'pengguna@nearby.id',
                'phone' => '0812-0000-0004', 'role' => 'user', 'password' => $user,
                'note' => 'Pengunjung biasa: ulasan & favorit',
            ],
        ];
    }
}
