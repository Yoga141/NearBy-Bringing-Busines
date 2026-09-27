<?php

namespace Tests\Feature;

use App\Models\Umkm;
use App\Models\UmkmPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload → storage → database → URL → served image.
 */
class UmkmPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function ownedUmkm(User $owner): Umkm
    {
        return Umkm::create([
            'owner_id' => $owner->id, 'name' => 'Warung Foto', 'category' => 'Kuliner',
            'location' => 'Balikpapan Kota', 'verification' => 'disetujui',
        ]);
    }

    public function test_an_owner_uploads_photos_that_are_stored_listed_and_served(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $umkm = $this->ownedUmkm($owner);

        $res = $this->actingAs($owner)->post("/api/umkm/{$umkm->id}/photos", [
            'photos' => [UploadedFile::fake()->image('depan.jpg', 800, 600), UploadedFile::fake()->image('menu.png')],
        ], ['Accept' => 'application/json']);

        $res->assertOk()->assertJsonCount(2, 'photos');
        $url = $res->json('coverUrl');
        $this->assertStringStartsWith('/api/umkm-photos/', $url);

        $photo = UmkmPhoto::where('umkm_id', $umkm->id)->orderBy('sort_order')->first();
        Storage::disk('public')->assertExists($photo->path);

        // Public list and detail carry the same URL, and it serves the file.
        $this->getJson('/api/umkm')->assertOk()->assertJsonPath('0.coverUrl', $url);
        $this->getJson("/api/umkm/{$umkm->id}")->assertOk()->assertJsonPath('photos.0.url', $url);
        $this->get($url)->assertOk();
    }

    public function test_setting_a_cover_and_deleting_a_photo_removes_the_file(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $umkm = $this->ownedUmkm($owner);
        $this->actingAs($owner)->post("/api/umkm/{$umkm->id}/photos", [
            'photos' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        [$a, $b] = UmkmPhoto::where('umkm_id', $umkm->id)->orderBy('sort_order')->get()->all();

        $this->actingAs($owner)->postJson("/api/umkm/{$umkm->id}/photos/{$b->id}/cover")
            ->assertOk()->assertJsonPath('photos.0.id', $b->id);

        $this->actingAs($owner)->deleteJson("/api/umkm/{$umkm->id}/photos/{$a->id}")->assertOk()->assertJsonCount(1, 'photos');
        Storage::disk('public')->assertMissing($a->path);
        $this->assertDatabaseMissing('umkm_photos', ['id' => $a->id]);
    }

    public function test_only_the_owner_or_an_admin_may_upload_and_only_images_are_accepted(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $umkm = $this->ownedUmkm($owner);
        $stranger = User::factory()->create(['role' => 'owner']);

        $this->actingAs($stranger)->post("/api/umkm/{$umkm->id}/photos", [
            'photos' => [UploadedFile::fake()->image('x.jpg')],
        ], ['Accept' => 'application/json'])->assertForbidden();

        $this->actingAs($owner)->post("/api/umkm/{$umkm->id}/photos", [
            'photos' => [UploadedFile::fake()->create('virus.pdf', 10, 'application/pdf')],
        ], ['Accept' => 'application/json'])->assertUnprocessable();

        $this->actingAs(User::factory()->create(['role' => 'admin']))->post("/api/umkm/{$umkm->id}/photos", [
            'photos' => [UploadedFile::fake()->image('admin.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();
    }

    public function test_deleting_a_umkm_for_good_removes_its_photo_files(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $umkm = $this->ownedUmkm($owner);
        $this->actingAs($owner)->post("/api/umkm/{$umkm->id}/photos", [
            'photos' => [UploadedFile::fake()->image('a.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();
        $path = UmkmPhoto::first()->path;

        $this->actingAs($owner)->deleteJson("/api/umkm/{$umkm->id}")->assertOk();
        Storage::disk('public')->assertExists($path); // soft delete keeps it for restore
        $this->actingAs($owner)->deleteJson("/api/owner/umkm/{$umkm->id}/force")->assertOk();

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseCount('umkm_photos', 0);
    }
}
