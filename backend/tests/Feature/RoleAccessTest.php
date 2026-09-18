<?php

namespace Tests\Feature;

use App\Models\Umkm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A plain "pengguna" account must not reach anything the dashboard offers -
 * mirroring the frontend router guard, which keeps role `user` out of
 * /dashboard entirely. Every such endpoint is listed here, so a route added
 * outside the `role:owner,admin` group fails this test instead of shipping.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function ownerOnlyEndpoints(): array
    {
        $endpoints = [
            'ajukan UMKM' => ['POST', '/api/umkm'],
            'ubah UMKM' => ['PUT', '/api/umkm/{id}'],
            'hapus UMKM' => ['DELETE', '/api/umkm/{id}'],
            'ringkasan owner' => ['GET', '/api/owner/summary'],
            'UMKM milik owner' => ['GET', '/api/owner/umkm'],
            'ulasan owner' => ['GET', '/api/owner/reviews'],
            'sampah owner' => ['GET', '/api/owner/trash'],
            'kosongkan sampah' => ['DELETE', '/api/owner/trash'],
            'pulihkan UMKM' => ['POST', '/api/owner/umkm/{id}/restore'],
            'hapus permanen' => ['DELETE', '/api/owner/umkm/{id}/force'],
        ];

        foreach (['umkm-excel', 'umkm-item-excel'] as $prefix) {
            foreach (['GET download', 'GET template', 'GET export', 'POST preview', 'POST commit'] as $action) {
                [$method, $path] = explode(' ', $action);
                $endpoints["Excel {$prefix} {$path}"] = [$method, "/api/{$prefix}/{$path}"];
            }
        }

        return $endpoints;
    }

    #[DataProvider('ownerOnlyEndpoints')]
    public function test_a_plain_user_is_refused(string $method, string $path): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $umkm = Umkm::create([
            'owner_id' => $user->id, // even "their own" row must not help
            'name' => 'Warung Uji',
            'category' => 'Kuliner',
            'location' => 'Balikpapan Kota',
        ]);
        $umkm->delete();

        $payload = [
            'name' => 'Usaha Terlarang',
            'category' => 'Kuliner',
            'location' => 'Balikpapan Kota',
            'rows' => [['name' => 'Usaha Terlarang', 'category' => 'Kuliner', 'location' => 'Balikpapan Kota']],
            'file' => UploadedFile::fake()->create('impor.xlsx', 1),
        ];

        $this->actingAs($user)
            ->json($method, str_replace('{id}', (string) $umkm->id, $path), $payload)
            ->assertForbidden()
            ->assertJsonPath('message', 'Fitur ini khusus pemilik UMKM.');

        $this->assertDatabaseMissing('umkms', ['name' => 'Usaha Terlarang']);
        $this->assertSoftDeleted($umkm);
    }

    #[DataProvider('ownerOnlyEndpoints')]
    public function test_a_guest_must_log_in(string $method, string $path): void
    {
        $this->json($method, str_replace('{id}', '1', $path))->assertUnauthorized();
    }

    public function test_a_user_cannot_promote_themselves_to_owner(): void
    {
        $user = User::factory()->create(['role' => 'user', 'email' => 'pengguna@example.test']);

        $this->actingAs($user)->putJson('/api/me', [
            'name' => 'Pengguna',
            'email' => 'pengguna@example.test',
            'role' => 'owner',
            'status' => 'aktif',
        ])->assertOk()->assertJsonPath('role', 'user');

        $this->assertSame('user', $user->fresh()->role);
        $this->actingAs($user->fresh())->postJson('/api/umkm', [
            'name' => 'Masih Terlarang', 'category' => 'Kuliner', 'location' => 'Balikpapan Kota',
        ])->assertForbidden();
    }

    public function test_the_admin_area_refuses_owners_and_users(): void
    {
        foreach (['user', 'owner'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->getJson('/api/admin/users')
                ->assertForbidden()
                ->assertJsonPath('message', 'Akses khusus admin.');
        }
    }

    public function test_a_plain_user_keeps_the_features_meant_for_visitors(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $umkm = Umkm::create([
            'name' => 'Warung Publik', 'category' => 'Kuliner',
            'location' => 'Balikpapan Kota', 'verification' => 'disetujui',
        ]);

        $this->actingAs($user)->postJson("/api/umkm/{$umkm->id}/reviews", ['stars' => 5])->assertCreated();
        $this->actingAs($user)->postJson("/api/umkm/{$umkm->id}/favorite")->assertOk()->assertJsonPath('isFavorite', true);
    }
}
