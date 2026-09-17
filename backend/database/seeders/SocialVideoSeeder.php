<?php

namespace Database\Seeders;

use App\Models\SocialVideo;
use Illuminate\Database\Seeder;

class SocialVideoSeeder extends Seeder
{
    /**
     * The three homepage video slots. Links are intentionally left empty — the
     * cards render as "Video belum tersedia" until an admin pastes the real
     * Instagram/YouTube link from the dashboard.
     */
    public function run(): void
    {
        $slots = [
            ['platform' => 'youtube', 'title' => 'Jelajah Oleh-Oleh Khas Balikpapan'],
            ['platform' => 'instagram', 'title' => 'Amplang & Kerupuk Kuku Macan'],
            ['platform' => 'youtube', 'title' => 'Cerita UMKM Kuliner Balikpapan'],
        ];

        foreach ($slots as $i => $slot) {
            SocialVideo::create($slot + ['url' => null, 'sort_order' => $i + 1, 'active' => true]);
        }
    }
}
