<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Starter data: the demo accounts, the demo UMKM catalogue and the empty
     * homepage video slots.
     *
     * Every seeder is idempotent, so `php artisan db:seed` can be re-run on a
     * database that already has data. UserSeeder must come first: UmkmSeeder
     * looks the demo owners up by email. Reviews and verification requests are
     * never seeded - they only come from real users.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            UmkmSeeder::class,
            SocialVideoSeeder::class,
        ]);
    }
}
