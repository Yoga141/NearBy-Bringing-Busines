<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Seed login accounts and only the real survey UMKM rows. */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            UmkmSeeder::class,
        ]);
    }
}
