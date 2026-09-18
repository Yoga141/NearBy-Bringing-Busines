<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database from the frontend mock data.
     *
     * Every seeder is idempotent, so `php artisan db:seed` can be re-run on a
     * database that already has data. UserSeeder must come first: UmkmSeeder
     * looks the demo owners up by email.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            UmkmSeeder::class,
            ReviewSeeder::class,
            SubmissionSeeder::class,
            SocialVideoSeeder::class,
        ]);
    }
}
