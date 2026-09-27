<?php

namespace Tests\Feature;

use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UmkmVisibilityAndRatingTest extends TestCase
{
    use RefreshDatabase;

    private function umkm(array $attributes = []): Umkm
    {
        return Umkm::create([
            'name' => 'Warung Uji',
            'category' => 'Kuliner',
            'location' => 'Balikpapan Kota',
            'verification' => 'disetujui',
            ...$attributes,
        ]);
    }

    public function test_a_pending_umkm_is_hidden_from_the_public_but_visible_to_its_owner(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $pending = $this->umkm(['owner_id' => $owner->id, 'verification' => 'menunggu']);

        $this->getJson("/api/umkm/{$pending->id}")->assertNotFound()
            ->assertJsonPath('message', 'Data yang dicari tidak ditemukan.');
        $this->getJson('/api/umkm')->assertOk()->assertJsonCount(0);

        // The detail route is public, so this goes through a real bearer
        // token rather than actingAs(), exactly like the SPA does.
        $token = $owner->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson("/api/umkm/{$pending->id}")->assertOk()->assertJsonPath('name', 'Warung Uji');
    }

    public function test_the_detail_page_reports_the_favourite_flag_for_a_signed_in_visitor(): void
    {
        $user = User::factory()->create();
        $umkm = $this->umkm();
        $user->favorites()->attach($umkm->id);

        $this->withToken($user->createToken('t')->plainTextToken)
            ->getJson("/api/umkm/{$umkm->id}")
            ->assertOk()
            ->assertJsonPath('isFavorite', true);
    }

    public function test_views_count_visitors_but_not_the_owner(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $umkm = $this->umkm(['owner_id' => $owner->id]);
        $updatedAt = $umkm->updated_at;

        $this->getJson("/api/umkm/{$umkm->id}")->assertOk()->assertJsonPath('views', 1);
        $this->withToken($owner->createToken('t')->plainTextToken)->getJson("/api/umkm/{$umkm->id}")->assertOk();

        $umkm->refresh();
        $this->assertSame(1, $umkm->views);
        $this->assertEquals($updatedAt, $umkm->updated_at, 'A page view is not an edit.');
    }

    public function test_search_treats_percent_signs_literally(): void
    {
        $this->umkm(['name' => 'Diskon 50% Hari Ini']);
        $this->umkm(['name' => 'Warung Biasa']);

        $this->getJson('/api/umkm?q='.urlencode('50%'))->assertOk()->assertJsonCount(1);
        $this->getJson('/api/umkm?q='.urlencode('%'))->assertOk()->assertJsonCount(1);
    }

    public function test_the_rating_is_recounted_from_real_reviews(): void
    {
        [$ana, $budi] = User::factory()->count(2)->create();
        $umkm = $this->umkm();

        $id = $this->actingAs($ana)->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 1, 'text' => 'Kurang'])
            ->assertCreated()->json('id');
        $this->actingAs($budi)->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 4, 'text' => 'Lumayan'])
            ->assertCreated();

        $umkm->refresh();
        $this->assertSame(2, $umkm->reviews_count);
        $this->assertSame(2.5, $umkm->rating);

        $this->actingAs($ana)->putJson("/api/reviews/{$id}", ['stars' => 5, 'text' => 'Ternyata enak'])->assertOk();
        $this->assertSame(4.5, $umkm->fresh()->rating);

        $this->actingAs($ana)->deleteJson("/api/reviews/{$id}")->assertOk();
        $umkm->refresh();
        $this->assertSame(1, $umkm->reviews_count);
        $this->assertSame(4.0, $umkm->rating);
    }

    public function test_rating_and_views_cannot_be_written_through_the_api(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $umkm = $this->umkm(['owner_id' => $owner->id]);

        $this->actingAs($owner)->putJson("/api/umkm/{$umkm->id}", [
            'rating' => 5, 'reviews_count' => 999, 'views' => 100000,
        ])->assertOk();

        $umkm->refresh();
        $this->assertSame(0.0, $umkm->rating);
        $this->assertSame(0, $umkm->reviews_count);
        $this->assertSame(0, $umkm->views);
    }

    public function test_refreshing_the_page_does_not_inflate_views(): void
    {
        $umkm = $this->umkm();
        $visitor = User::factory()->create();
        $token = $visitor->createToken('t')->plainTextToken;

        for ($i = 0; $i < 5; $i++) {
            $this->getJson("/api/umkm/{$umkm->id}")->assertOk();
            $this->withToken($token)->getJson("/api/umkm/{$umkm->id}")->assertOk();
        }

        // One guest (same IP + browser) and one signed-in visitor.
        $this->assertSame(2, $umkm->fresh()->views);
    }

    public function test_an_admin_created_umkm_is_published_without_a_queue_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/umkm', [
            'name' => 'Toko Admin', 'category' => 'Jasa', 'location' => 'Balikpapan Barat',
        ])->assertCreated()->assertJsonPath('verification', 'disetujui');

        $this->assertDatabaseMissing('submissions', ['name' => 'Toko Admin']);
        $this->getJson('/api/umkm')->assertOk()->assertJsonPath('0.name', 'Toko Admin');
    }

    public function test_only_owners_and_admins_may_submit_a_umkm(): void
    {
        $payload = ['name' => 'Usaha Baru', 'category' => 'Jasa', 'location' => 'Balikpapan Barat'];

        $this->actingAs(User::factory()->create(['role' => 'user']))->postJson('/api/umkm', $payload)->assertForbidden();

        $owner = User::factory()->create(['role' => 'owner']);
        $this->actingAs($owner)->postJson('/api/umkm', [...$payload, 'items' => [['name' => 'Servis', 'price' => 'Rp10rb']]])
            ->assertCreated()
            ->assertJsonPath('verification', 'menunggu')
            ->assertJsonPath('items.0.name', 'Servis');

        $this->assertDatabaseHas('submissions', ['name' => 'Usaha Baru', 'owner_id' => $owner->id, 'status' => 'menunggu']);
    }
}
