<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * One admin, one regular user and one owner account for each survey district.
 *
 *   php artisan db:seed --class=UserSeeder     (accounts only)
 *   php artisan migrate:fresh --seed            (everything)
 *
 * Safe to run repeatedly: accounts are matched by email and brought back to
 * the configured state instead of failing on the unique email. A soft-deleted
 * account is restored.
 *
 * Passwords can be overridden from .env (SEED_*_PASSWORD) so a shared or
 * production environment never ends up with the public defaults below. env()
 * is safe here only because seeders run from the CLI; request-time code must
 * go through config() instead.
 */
class UserSeeder extends Seeder
{
    /** @var array<string, string> Kecamatan to owner account for imported rows. */
    public const DISTRICT_OWNER_EMAILS = [
        'Balikpapan Kota' => 'pemilik.kota@nearby.id',
        'Balikpapan Utara' => 'pemilik.utara@nearby.id',
        'Balikpapan Barat' => 'pemilik.barat@nearby.id',
        'Balikpapan Timur' => 'pemilik.timur@nearby.id',
        'Balikpapan Tengah' => 'pemilik.tengah@nearby.id',
        'Balikpapan Selatan' => 'pemilik.selatan@nearby.id',
    ];

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
        $this->command?->info('Akun awal siap dipakai untuk login:');
        $this->command?->table(['Peran', 'Email', 'Password', 'Keterangan'], $rows);
    }

    /**
     * @return list<array{label: string, name: string, email: string, phone: string, role: string, password: string, note: string}>
     */
    private function accounts(): array
    {
        // `?:` instead of env()'s default: a key that is present but blank
        // (`SEED_ADMIN_PASSWORD=`) returns "" and would seed an empty password.
        $admin = env('SEED_ADMIN_PASSWORD');
        $owner = env('SEED_OWNER_PASSWORD');
        $user = env('SEED_USER_PASSWORD');

        // A live server must never get accounts with the passwords printed in
        // the README.
        if (app()->isProduction() && ! (env('SEED_ADMIN_PASSWORD') && env('SEED_OWNER_PASSWORD') && env('SEED_USER_PASSWORD'))) {
            throw new \RuntimeException(
                'APP_ENV=production: isi SEED_ADMIN_PASSWORD, SEED_OWNER_PASSWORD, dan SEED_USER_PASSWORD di .env sebelum menjalankan seeder.'
            );
        }

        $accounts = [
            [
                'label' => 'Admin', 'name' => 'Admin NearBy', 'email' => 'admin@gmail.com',
                'phone' => null, 'role' => 'admin', 'password' => $admin,
                'note' => 'Dashboard admin: verifikasi, pengguna, laporan',
            ],
            [
                'label' => 'Pengguna', 'name' => 'Pengguna NearBy', 'email' => 'pengguna@nearby.id',
                'phone' => null, 'role' => 'user', 'password' => $user,
                'note' => 'Pengunjung biasa: ulasan & favorit',
            ],
        ];

        foreach (self::DISTRICT_OWNER_EMAILS as $district => $email) {
            $suffix = str($district)->after('Balikpapan ')->lower()->value();
            $accounts[] = [
                'label' => 'Pemilik UMKM',
                'name' => 'Pemilik UMKM Balikpapan '.$suffix,
                'email' => $email,
                'phone' => null,
                'role' => 'owner',
                'password' => $owner,
                'note' => 'Pengelola UMKM Kecamatan Balikpapan '.$suffix,
            ];
        }

        return $accounts;
    }
}
