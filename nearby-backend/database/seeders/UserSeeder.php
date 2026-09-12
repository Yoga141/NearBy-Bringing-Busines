<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * One demo account per role, mirroring the frontend's mocked logins:
     *  - user  : Rizky Pratama
     *  - owner : Dewi Anjani  (owns UMKM ids 1 & 4 — see UmkmSeeder, which
     *            assumes she is the 2nd user created, i.e. id 2)
     *  - admin : Admin NearBy
     *
     * Passwords come from .env (SEED_*_PASSWORD) instead of being hardcoded,
     * so a shared/production environment can seed these accounts with real
     * passwords instead of the "password" placeholder. Note: env() reads
     * .env directly and is only safe here because seeders run as CLI
     * commands — if this were request-time app code, a cached config
     * (`config:cache`) would make env() return null.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Jeki',
                'email' => 'jekikilo15@mail.com',
                'role' => 'user',
                'status' => 'aktif',
                'password' => env('SEED_USER_PASSWORD', 'password'),
            ],
            [
                'name' => 'Dewi Anjani',
                'email' => 'dewi@mail.com',
                'role' => 'owner',
                'status' => 'aktif',
                'password' => env('SEED_OWNER_PASSWORD', 'password'),
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@nearby.id',
                'role' => 'admin',
                'status' => 'aktif',
                'password' => env('SEED_ADMIN_PASSWORD', 'password'),
            ],
        ];

        foreach ($accounts as $account) {
            User::create([
                ...$account,
                'phone' => '0812-0000-0000',
            ]);
        }
    }
}
