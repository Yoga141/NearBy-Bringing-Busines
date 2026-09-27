<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAndAccountTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_register_stores_the_account_and_it_can_log_in_again(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Rina Baru', 'email' => 'Rina@Example.test', 'password' => 'rahasia123', 'role' => 'user',
        ])->assertCreated()->assertJsonPath('user.email', 'rina@example.test');

        $this->assertDatabaseHas('users', ['email' => 'rina@example.test', 'role' => 'user', 'status' => 'aktif']);

        $token = $this->postJson('/api/login', ['email' => 'rina@example.test', 'password' => 'rahasia123'])
            ->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/me')->assertOk()->assertJsonPath('name', 'Rina Baru');

        $this->postJson('/api/register', ['name' => 'X', 'email' => 'rina@example.test', 'password' => 'rahasia123'])
            ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Email ini sudah terdaftar. Silakan masuk.');
        $this->postJson('/api/register', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_reports_are_computed_from_real_rows(): void
    {
        $umkm = Umkm::create(['name' => 'A', 'category' => 'Jasa', 'location' => 'Balikpapan Kota', 'verification' => 'disetujui']);
        Umkm::create(['name' => 'B', 'category' => 'Jasa', 'location' => 'Balikpapan Kota', 'verification' => 'menunggu']);
        [$u1, $u2] = User::factory()->count(2)->create();
        Review::create(['umkm_id' => $umkm->id, 'user_id' => $u1->id, 'stars' => 5, 'text' => 'x']);
        Review::create(['umkm_id' => $umkm->id, 'user_id' => $u2->id, 'stars' => 2, 'text' => 'y']);

        $this->actingAs($this->admin())->getJson('/api/admin/reports')->assertOk()
            ->assertJsonPath('stats.umkmCount', 2)
            ->assertJsonPath('stats.publishedCount', 1)
            ->assertJsonPath('stats.pendingCount', 1)
            ->assertJsonPath('stats.reviewCount', 2)
            ->assertJsonPath('stats.avgRating', 3.5);
    }

    public function test_admin_manages_users_and_umkm_trash(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $user->createToken('t');

        $this->actingAs($admin)->deleteJson("/api/admin/users/{$user->id}")->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->actingAs($admin)->deleteJson("/api/admin/users/{$admin->id}")->assertForbidden();
        $this->actingAs($admin)->postJson("/api/admin/trash/{$user->id}/restore?type=user")->assertOk();
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);

        $umkm = Umkm::create(['name' => 'Hapus Aku', 'category' => 'Jasa', 'location' => 'Balikpapan Kota', 'verification' => 'disetujui']);
        $this->actingAs($admin)->putJson("/api/umkm/{$umkm->id}", ['name' => 'Diubah Admin'])->assertOk()->assertJsonPath('name', 'Diubah Admin');
        $this->actingAs($admin)->deleteJson("/api/umkm/{$umkm->id}")->assertOk();
        $this->actingAs($admin)->getJson('/api/admin/trash')->assertOk()->assertJsonPath('umkms.0.name', 'Diubah Admin');
        $this->actingAs($admin)->deleteJson("/api/admin/trash/umkm/{$umkm->id}")->assertOk();
        $this->assertDatabaseMissing('umkms', ['id' => $umkm->id]);
    }

    public function test_admin_issues_a_temporary_password_that_works(): void
    {
        $user = User::factory()->create(['email' => 'lupa@example.test', 'password' => 'lama12345']);
        $user->createToken('t');

        $temp = $this->actingAs($this->admin())->postJson("/api/admin/users/{$user->id}/reset-password")
            ->assertOk()->json('temporaryPassword');

        $this->assertSame(12, strlen($temp));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/login', ['email' => 'lupa@example.test', 'password' => 'lama12345'])->assertUnprocessable();
        $this->postJson('/api/login', ['email' => 'lupa@example.test', 'password' => $temp])->assertOk();
    }

    public function test_non_admins_cannot_reach_admin_actions(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $victim = User::factory()->create();

        $this->actingAs($owner)->deleteJson("/api/admin/users/{$victim->id}")->assertForbidden();
        $this->actingAs($owner)->getJson('/api/admin/reviews')->assertForbidden();
        $this->actingAs($owner)->postJson("/api/admin/users/{$victim->id}/reset-password")->assertForbidden();
        $this->assertNotSoftDeleted('users', ['id' => $victim->id]);
    }

    public function test_a_user_deletes_their_own_account_with_the_password(): void
    {
        $user = User::factory()->create(['email' => 'hapus@example.test', 'password' => 'rahasia123']);
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->deleteJson('/api/me', ['password' => 'salah'])
            ->assertUnprocessable()->assertJsonPath('errors.password.0', 'Kata sandi salah.');
        $this->withToken($token)->deleteJson('/api/me', ['password' => 'rahasia123'])->assertOk();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->postJson('/api/login', ['email' => 'hapus@example.test', 'password' => 'rahasia123'])->assertUnprocessable();
    }
}
