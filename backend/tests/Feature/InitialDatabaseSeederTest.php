<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Umkm;
use App\Models\UmkmItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_only_excel_businesses_and_district_accounts_idempotently(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 8);
        $this->assertDatabaseHas('users', ['email' => 'admin@gmail.com', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'pengguna@nearby.id', 'role' => 'user']);
        foreach ([
            'pemilik.kota@nearby.id',
            'pemilik.utara@nearby.id',
            'pemilik.barat@nearby.id',
            'pemilik.timur@nearby.id',
            'pemilik.tengah@nearby.id',
            'pemilik.selatan@nearby.id',
        ] as $email) {
            $this->assertDatabaseHas('users', ['email' => $email, 'role' => 'owner']);
        }

        $this->assertDatabaseCount('umkms', 6);
        $this->assertDatabaseCount('umkm_items', 0);
        $this->assertDatabaseCount('social_videos', 0);
        $this->assertDatabaseMissing('umkms', ['name' => 'Warung Kepiting Kenari']);
        $this->assertDatabaseMissing('umkms', ['name' => 'Kopi Saluang']);
        $this->assertDatabaseMissing('umkms', ['name' => 'Batik Beruang Madu']);

        $cityOwner = User::where('email', 'pemilik.kota@nearby.id')->firstOrFail();
        $this->assertSame(1, $cityOwner->umkms()->count());
        $this->assertDatabaseHas('umkms', [
            'name' => 'Warung Contoh Rasa',
            'location' => 'Balikpapan Kota',
            'owner_id' => $cityOwner->id,
        ]);
        $this->assertDatabaseHas('umkm_photos', [
            'umkm_id' => $cityOwner->umkms()->firstOrFail()->id,
            'url' => 'https://contoh.com/foto-warung.jpg',
        ]);
        $this->getJson('/api/umkm?location=Balikpapan%20Kota')
            ->assertOk()
            ->assertJsonCount(1);

        $northOwner = User::where('email', 'pemilik.utara@nearby.id')->firstOrFail();
        $this->assertSame(5, $northOwner->umkms()->count());
        foreach ([
            'pemilik.kota@nearby.id',
            'pemilik.barat@nearby.id',
            'pemilik.timur@nearby.id',
            'pemilik.tengah@nearby.id',
            'pemilik.selatan@nearby.id',
        ] as $email) {
            $this->assertSame(0, User::where('email', $email)->firstOrFail()->umkms()->count());
        }
        $this->assertDatabaseHas('umkms', [
            'name' => 'Teh Kita',
            'category' => 'Minuman',
            'owner_id' => $northOwner->id,
            'rating' => 0,
            'reviews_count' => 0,
            'views' => 0,
        ]);
        $this->assertDatabaseHas('umkms', [
            'name' => 'Warung Ibu Kaya',
            'category' => 'Toko Sayur & Buah',
            'owner_id' => $northOwner->id,
        ]);

        $this->getJson('/api/umkm?category=Minuman')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_new_categories_are_accepted_for_owner_submissions(): void
    {
        $this->seed();
        $owner = User::where('email', 'pemilik.utara@nearby.id')->firstOrFail();

        foreach (['Minuman', 'Toko Sayur & Buah'] as $category) {
            $name = 'Usaha '.$category;

            $this->actingAs($owner)->postJson('/api/umkm', [
                'name' => $name,
                'category' => $category,
                'location' => 'Balikpapan Utara',
            ])->assertCreated();

            $this->assertDatabaseHas('submissions', [
                'name' => $name,
                'category' => $category,
            ]);
        }
    }

    public function test_seeding_removes_only_legacy_demo_catalog_and_menu_without_deleting_reviews(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $legacyNames = [
            'Warung Kepiting Kenari',
            'Kopi Saluang',
            'Penginapan Teluk Asri',
            'Amplang Bahari',
            'Batik Beruang Madu',
            'Servis Motor Pak Gultom',
            'Nasi Kuning Sambal Raja',
            'Kriya Rotan Manggar',
            'Wisma Somber Stay',
            'Laundry Kilat Sepinggan',
        ];

        foreach ($legacyNames as $index => $name) {
            $umkm = Umkm::forceCreate([
                'id' => $index + 1,
                'owner_id' => $owner->id,
                'name' => $name,
                'category' => 'Kuliner',
                'location' => 'Balikpapan Kota',
                'status' => 'aktif',
                'verification' => 'disetujui',
            ]);
            UmkmItem::create(['umkm_id' => $umkm->id, 'name' => 'Menu dummy']);
        }

        Review::create([
            'umkm_id' => 1,
            'user_id' => null,
            'author_name' => 'Pengunjung',
            'stars' => 5,
            'text' => 'Ulasan tetap disimpan',
        ]);

        $this->seed();

        $this->assertSame(6, Umkm::query()->count());
        $this->assertSame(10, Umkm::onlyTrashed()->count());
        $this->assertDatabaseCount('umkm_items', 0);
        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['text' => 'Ulasan tetap disimpan']);
    }
}
