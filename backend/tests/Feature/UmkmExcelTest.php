<?php

namespace Tests\Feature;

use App\Models\Submission;
use App\Models\Umkm;
use App\Models\User;
use App\Support\Xlsx\XlsxReader;
use App\Support\Xlsx\XlsxWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The server-side .xlsx flow: real files in, real files out.
 */
class UmkmExcelTest extends TestCase
{
    use RefreshDatabase;

    private const HEADERS = ['ID', 'Nama Usaha', 'Kategori', 'Wilayah', 'Status'];

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

    /** @param list<list<mixed>> $rows */
    private function xlsxUpload(array $rows, array $headers = self::HEADERS): UploadedFile
    {
        $path = (new XlsxWriter)->addSheet('UMKM', [], [$headers, ...$rows])->saveToTemp();

        return new UploadedFile($path, 'impor.xlsx', null, null, true);
    }

    /** @return array<int, array<int, mixed>> */
    private function readDownload(TestResponse $res): array
    {
        $reader = new XlsxReader($res->baseResponse->getFile()->getPathname());
        $rows = $reader->rows();
        $reader->close();

        return $rows;
    }

    public function test_download_is_a_real_xlsx_with_only_the_owners_rows(): void
    {
        $owner = $this->owner();
        $this->umkmFor($owner, 'Punyaku');
        $this->umkmFor($this->owner('other@example.test'), 'Punya Orang');

        $res = $this->actingAs($owner)->get('/api/umkm-excel/download');

        $res->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('umkm-nearby-', $res->headers->get('content-disposition'));

        $rows = $this->readDownload($res);
        $this->assertSame('Nama Usaha', $rows[1][1]);
        $this->assertNotContains('Verifikasi', $rows[1], 'Owners must not get admin-only columns.');
        $this->assertCount(2, $rows); // header + 1 row
        $this->assertSame('Punyaku', $rows[2][1]);
        $this->assertSame('Aktif', $rows[2][10]);
    }

    public function test_an_owner_with_nothing_to_export_gets_a_clear_message(): void
    {
        $this->actingAs($this->owner())->getJson('/api/umkm-excel/download')
            ->assertNotFound()
            ->assertJsonPath('message', 'Belum ada data untuk diekspor.');
    }

    public function test_template_has_an_example_row_and_a_guide_sheet(): void
    {
        $res = $this->actingAs($this->owner())->get('/api/umkm-excel/template');
        $res->assertOk();

        $reader = new XlsxReader($res->baseResponse->getFile()->getPathname());
        $this->assertSame(['UMKM', 'Petunjuk'], $reader->sheetNames());
        $this->assertSame('Warung Contoh Rasa', $reader->rows()[2][1]);
        $reader->close();
    }

    public function test_an_exported_file_imports_back_unchanged(): void
    {
        $owner = $this->owner();
        $this->umkmFor($owner, 'Warung A');
        $this->umkmFor($owner, 'Warung B');

        $download = $this->actingAs($owner)->get('/api/umkm-excel/download');
        $file = new UploadedFile($download->baseResponse->getFile()->getPathname(), 'export.xlsx', null, null, true);

        $this->actingAs($owner)->post('/api/umkm-excel/preview', ['file' => $file], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('summary.update', 2)
            ->assertJsonPath('summary.create', 0)
            ->assertJsonPath('summary.error', 0);
    }

    public function test_uploading_a_sheet_creates_and_updates_rows(): void
    {
        $owner = $this->owner();
        $existing = $this->umkmFor($owner, 'Nama Lama');

        $file = $this->xlsxUpload([
            [$existing->id, 'Nama Baru', null, null, 'Libur'],
            [null, 'Usaha Baru', 'Jasa', 'Balikpapan Barat', null],
        ]);

        $this->actingAs($owner)->post('/api/umkm-excel/commit', ['file' => $file], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('summary.update', 1)
            ->assertJsonPath('summary.create', 1);

        $existing->refresh();
        $this->assertSame('Nama Baru', $existing->name);
        $this->assertSame('libur', $existing->status);
        // Blank cells on update leave the old value.
        $this->assertSame('Kuliner', $existing->category);

        $created = Umkm::where('name', 'Usaha Baru')->firstOrFail();
        $this->assertSame('menunggu', $created->verification, 'An owner import must never publish directly.');
        $this->assertTrue(Submission::where('umkm_id', $created->id)->exists());
    }

    public function test_errors_point_at_the_real_sheet_row(): void
    {
        $owner = $this->owner();

        // Row 3 is left blank on purpose: the bad row is on sheet line 4.
        $path = (new XlsxWriter)->addSheet('UMKM', [], [
            self::HEADERS,
            [null, 'Baik', 'Kuliner', 'Balikpapan Kota'],
            [],
            [null, 'Salah', 'Elektronik', 'Balikpapan Kota'],
        ])->saveToTemp();
        $file = new UploadedFile($path, 'impor.xlsx', null, null, true);

        $res = $this->actingAs($owner)->post('/api/umkm-excel/commit', ['file' => $file], ['Accept' => 'application/json']);

        $res->assertStatus(422)->assertJsonPath('summary.error', 1);
        $error = collect($res->json('rows'))->firstWhere('action', 'error');
        $this->assertSame(4, $error['row']);
        $this->assertStringStartsWith('Kategori tidak dikenali.', $error['messages'][0]);
        // All-or-nothing: the good row was not written either.
        $this->assertDatabaseMissing('umkms', ['name' => 'Baik']);
    }

    public function test_a_file_that_is_not_xlsx_is_rejected_politely(): void
    {
        $file = UploadedFile::fake()->createWithContent('data.xlsx', 'ini bukan file excel');

        $this->actingAs($this->owner())->post('/api/umkm-excel/preview', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'File tidak bisa dibaca. Pastikan formatnya .xlsx (bukan .xls atau .csv).');
    }

    public function test_a_sheet_without_the_name_column_is_rejected(): void
    {
        $file = $this->xlsxUpload([[1, 'Kuliner']], ['ID', 'Kategori']);

        $this->actingAs($this->owner())->post('/api/umkm-excel/preview', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('errors.file.0', 'Kolom "Nama Usaha" tidak ditemukan di file ini.');
    }

    public function test_an_owner_cannot_update_someone_elses_umkm_through_a_sheet(): void
    {
        $theirs = $this->umkmFor($this->owner('other@example.test'), 'Bukan Punyamu');
        $file = $this->xlsxUpload([[$theirs->id, 'Dibajak']]);

        $this->actingAs($this->owner())->post('/api/umkm-excel/commit', ['file' => $file], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertSame('Bukan Punyamu', $theirs->fresh()->name);
    }

    public function test_admin_can_import_directly_approved_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $file = $this->xlsxUpload(
            [[null, 'Dari Admin', 'Kuliner', 'Balikpapan Kota', 'Aktif', 'Disetujui']],
            [...self::HEADERS, 'Verifikasi'],
        );

        $this->actingAs($admin)->post('/api/umkm-excel/commit', ['file' => $file], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertDatabaseHas('umkms', ['name' => 'Dari Admin', 'verification' => 'disetujui']);
    }

    public function test_plain_users_cannot_reach_the_excel_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->getJson('/api/umkm-excel/download')->assertForbidden();
        $this->actingAs($user)->postJson('/api/umkm-excel/commit', ['rows' => [['name' => 'x']]])->assertForbidden();
    }

    public function test_product_sheet_round_trips_ya_tidak(): void
    {
        $owner = $this->owner();
        $umkm = $this->umkmFor($owner);
        $path = (new XlsxWriter)->addSheet('Produk', [], [
            ['ID', 'ID UMKM', 'Nama Produk', 'Harga', 'Tersedia'],
            [null, $umkm->id, 'Es Teh', 'Rp5.000', 'Tidak'],
        ])->saveToTemp();

        $this->actingAs($owner)->post('/api/umkm-item-excel/commit', [
            'file' => new UploadedFile($path, 'produk.xlsx', null, null, true),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertDatabaseHas('umkm_items', ['name' => 'Es Teh', 'available' => false]);

        $rows = $this->readDownload($this->actingAs($owner)->get('/api/umkm-item-excel/download'));
        $this->assertSame('Tidak', $rows[2][6]);
    }
}
