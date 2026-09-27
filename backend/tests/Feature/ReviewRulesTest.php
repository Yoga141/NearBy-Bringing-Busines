<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One review per user per UMKM: add once, then edit.
 */
class ReviewRulesTest extends TestCase
{
    use RefreshDatabase;

    private function umkm(array $attributes = []): Umkm
    {
        return Umkm::create([
            'name' => 'Warung Ulasan', 'category' => 'Kuliner',
            'location' => 'Balikpapan Kota', 'verification' => 'disetujui',
            ...$attributes,
        ]);
    }

    public function test_a_second_review_is_refused_and_points_at_the_first(): void
    {
        $user = User::factory()->create();
        $umkm = $this->umkm();

        $first = $this->actingAs($user)->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 4, 'text' => 'Enak'])
            ->assertCreated()->json('id');

        $this->actingAs($user)->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 1, 'text' => 'Spam'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Kamu sudah memberi ulasan untuk UMKM ini. Silakan edit ulasanmu.')
            ->assertJsonPath('review.id', $first);

        $this->assertSame(1, Review::where('umkm_id', $umkm->id)->count());
        $this->assertSame(4.0, $umkm->fresh()->rating);
    }

    public function test_the_database_itself_refuses_a_duplicate(): void
    {
        $user = User::factory()->create();
        $umkm = $this->umkm();
        Review::create(['umkm_id' => $umkm->id, 'user_id' => $user->id, 'stars' => 5, 'text' => 'A']);

        $this->expectException(UniqueConstraintViolationException::class);
        Review::create(['umkm_id' => $umkm->id, 'user_id' => $user->id, 'stars' => 1, 'text' => 'B']);
    }

    public function test_the_author_can_edit_and_others_cannot(): void
    {
        [$author, $other] = User::factory()->count(2)->create();
        $umkm = $this->umkm();
        $id = $this->actingAs($author)->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 3, 'text' => 'Biasa'])->json('id');

        $this->actingAs($other)->putJson("/api/reviews/{$id}", ['stars' => 1, 'text' => 'Diubah orang'])->assertForbidden();
        $this->actingAs($author)->putJson("/api/reviews/{$id}", ['stars' => 5, 'text' => 'Ternyata mantap'])
            ->assertOk()->assertJsonPath('text', 'Ternyata mantap')->assertJsonPath('stars', 5);

        $this->assertDatabaseHas('reviews', ['id' => $id, 'text' => 'Ternyata mantap', 'stars' => 5]);
    }

    public function test_empty_or_invalid_reviews_are_rejected(): void
    {
        $user = User::factory()->create();
        $umkm = $this->umkm();

        $this->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 5, 'text' => 'Tamu'])->assertUnauthorized();

        $this->actingAs($user)->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 5, 'text' => '   '])
            ->assertUnprocessable()->assertJsonPath('errors.text.0', 'Komentar tidak boleh kosong.');
        $this->actingAs($user)->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 9, 'text' => 'Bagus'])
            ->assertUnprocessable()->assertJsonValidationErrors('stars');
    }

    public function test_an_owner_cannot_review_their_own_umkm_and_hidden_umkm_cannot_be_reviewed(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $mine = $this->umkm(['owner_id' => $owner->id]);
        $pending = $this->umkm(['name' => 'Belum Tampil', 'verification' => 'menunggu']);

        $this->actingAs($owner)->postJson("/api/umkm/{$mine->id}/reviews", ['stars' => 5, 'text' => 'Punyaku bagus'])->assertForbidden();
        $this->actingAs(User::factory()->create())->postJson("/api/umkm/{$pending->id}/reviews", ['stars' => 5, 'text' => 'Halo'])->assertNotFound();
    }

    public function test_an_admin_can_moderate_any_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $umkm = $this->umkm();
        $id = $this->actingAs(User::factory()->create())->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 1, 'text' => 'Kasar'])->json('id');

        $this->actingAs($admin)->getJson('/api/admin/reviews')->assertOk()->assertJsonPath('0.umkmName', 'Warung Ulasan');
        $this->actingAs($admin)->deleteJson("/api/reviews/{$id}")->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $id]);
        $this->assertSame(0, $umkm->fresh()->reviews_count);
    }
}
