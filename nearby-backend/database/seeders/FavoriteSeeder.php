<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Demo favorites for the "user" role accounts (see UserSeeder), so the
     * `favorites` pivot table isn't left empty after a fresh seed.
     */
    public function run(): void
    {
        $favorites = [
            'rizky.p@mail.com' => [1, 2, 4, 7],
            'maya.s@mail.com' => [3, 5, 9],
        ];

        foreach ($favorites as $email => $umkmIds) {
            User::whereEmail($email)->first()?->favorites()->attach($umkmIds);
        }
    }
}
