<?php

namespace Database\Seeders;

use App\Models\SocialVideo;
use Illuminate\Database\Seeder;

class SocialVideoSeeder extends Seeder
{
    /**
     * The three homepage video slots. Links are intentionally left empty - the
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

        // Keyed by position so re-running never adds extra slots or wipes a
        // link an admin has already pasted.
        foreach ($slots as $i => $slot) {
            SocialVideo::firstOrCreate(['sort_order' => $i + 1], $slot + ['url' => null, 'active' => true]);
        }
    }
}
