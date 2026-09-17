<?php

namespace Tests\Feature;

use App\Models\Umkm;
use App\Models\UmkmItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UmkmItemPortTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $email = 'owner@example.test'): User
    {
        return User::factory()->create(['email' => $email, 'role' => 'owner']);
    }

    private function umkmFor(User $owner, string $name = 'Warung Uji'): Umkm
    {
        return Umkm::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'category' => 'Kuliner',
            'location' => 'Balikpapan Kota',
            'status' => 'aktif',
            'verification' => 'disetujui',
        ]);
    }

    public function test_owner_exports_only_products_of_their_own_umkm(): void
    {
        $owner = $this->owner();
        $mine = $this->umkmFor($owner);
        $theirs = $this->umkmFor($this->owner('other@example.test'), 'Warung Lain');

        UmkmItem::create(['umkm_id' => $mine->id, 'name' => 'Nasi Kuning', 'price' => 'Rp20.000']);
        UmkmItem::create(['umkm_id' => $theirs->id, 'name' => 'Soto Banjar']);

        $res = $this->actingAs($owner)->getJson('/api/umkm-item-excel/export');

        $res->assertOk()->assertJsonPath('isAdmin', false)->assertJsonCount(1, 'rows');
        $res->assertJsonPath('rows.0.name', 'Nasi Kuning');
        $res->assertJsonPath('rows.0.umkm_name', 'Warung Uji');
        $res->assertJsonPath('rows.0.available', true);
    }

    public function test_preview_reports_actions_without_writing(): void
    {
        $owner = $this->owner();
        $umkm = $this->umkmFor($owner);
        $item = UmkmItem::create(['umkm_id' => $umkm->id, 'name' => 'Lama']);

        $res = $this->actingAs($owner)->postJson('/api/umkm-item-excel/preview', [
            'rows' => [
                ['id' => null, 'umkm_id' => $umkm->id, 'name' => 'Produk Baru', 'price' => 'Rp5.000'],
                ['id' => $item->id, 'name' => 'Nama Diperbarui'],
            ],
        ]);

        $res->assertOk()
            ->assertJsonPath('summary.create', 1)
            ->assertJsonPath('summary.update', 1)
            ->assertJsonPath('summary.error', 0);

        $this->assertSame('Lama', $item->fresh()->name);
        $this->assertSame(1, UmkmItem::count());
    }

    public function test_commit_creates_and_updates_products(): void
    {
        $owner = $this->owner();
        $umkm = $this->umkmFor($owner);
        $item = UmkmItem::create(['umkm_id' => $umkm->id, 'name' => 'Lama', 'available' => true]);

        $res = $this->actingAs($owner)->postJson('/api/umkm-item-excel/commit', [
            'rows' => [
                ['umkm_id' => $umkm->id, 'name' => 'Produk Baru', 'price' => 'Rp5.000'],
                ['id' => $item->id, 'name' => 'Nama Diperbarui', 'available' => false],
            ],
        ]);

        $res->assertOk()->assertJsonPath('summary.create', 1)->assertJsonPath('summary.update', 1);

        $this->assertDatabaseHas('umkm_items', [
            'umkm_id' => $umkm->id,
            'name' => 'Produk Baru',
            'price' => 'Rp5.000',
            'available' => true,
        ]);
        $updated = $item->fresh();
        $this->assertSame('Nama Diperbarui', $updated->name);
        $this->assertFalse($updated->available);
    }

    public function test_owner_cannot_write_products_into_someone_elses_umkm(): void
    {
        $owner = $this->owner();
        $this->umkmFor($owner);
        $theirs = $this->umkmFor($this->owner('other@example.test'), 'Warung Lain');

        $res = $this->actingAs($owner)->postJson('/api/umkm-item-excel/commit', [
            'rows' => [
                ['umkm_id' => $theirs->id, 'name' => 'Nyelundup'],
            ],
        ]);

        $res->assertStatus(422);
        $this->assertDatabaseMissing('umkm_items', ['name' => 'Nyelundup']);
    }

    public function test_a_single_bad_row_blocks_the_whole_sheet(): void
    {
        $owner = $this->owner();
        $umkm = $this->umkmFor($owner);

        $res = $this->actingAs($owner)->postJson('/api/umkm-item-excel/commit', [
            'rows' => [
                ['umkm_id' => $umkm->id, 'name' => 'Baik'],
                ['umkm_id' => 999999, 'name' => 'Induk Hilang'],
            ],
        ]);

        $res->assertStatus(422)->assertJsonPath('summary.error', 1);
        // Nothing half-applied: the valid row in the same sheet is not written.
        $this->assertSame(0, UmkmItem::count());
    }

    public function test_admin_sees_and_writes_every_product(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.test', 'role' => 'admin']);
        $umkm = $this->umkmFor($this->owner(), 'Warung Orang');
        UmkmItem::create(['umkm_id' => $umkm->id, 'name' => 'Punya Orang']);

        $this->actingAs($admin)->getJson('/api/umkm-item-excel/export')
            ->assertOk()
            ->assertJsonPath('isAdmin', true)
            ->assertJsonCount(1, 'rows');

        $this->actingAs($admin)->postJson('/api/umkm-item-excel/commit', [
            'rows' => [['umkm_id' => $umkm->id, 'name' => 'Ditambah Admin']],
        ])->assertOk();

        $this->assertDatabaseHas('umkm_items', ['name' => 'Ditambah Admin']);
    }
}
