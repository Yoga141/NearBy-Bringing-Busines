<?php

namespace Database\Seeders;

use App\Models\Umkm;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UmkmSeeder extends Seeder
{
    /** @var array<int, string> Exact IDs and names from the retired demo seeder. */
    private const LEGACY_DEMO_UMKMS = [
        1 => 'Warung Kepiting Kenari',
        2 => 'Kopi Saluang',
        3 => 'Penginapan Teluk Asri',
        4 => 'Amplang Bahari',
        5 => 'Batik Beruang Madu',
        6 => 'Servis Motor Pak Gultom',
        7 => 'Nasi Kuning Sambal Raja',
        8 => 'Kriya Rotan Manggar',
        9 => 'Wisma Somber Stay',
        10 => 'Laundry Kilat Sepinggan',
    ];

    /**
     * Seed only the six business rows in the supplied survey spreadsheet.
     * Retire the exact previous demo catalogue without deleting its reviews.
     * The workbook's Kota row and external photo link are included.
     */
    public function run(): void
    {
        $this->removeLegacyDemoData();

        $ownerIds = User::whereIn('email', array_values(UserSeeder::DISTRICT_OWNER_EMAILS))
            ->pluck('id', 'email');

        foreach ($this->data() as $row) {
            $ownerEmail = UserSeeder::DISTRICT_OWNER_EMAILS[$row['location']] ?? null;
            if ($ownerEmail === null || ! $ownerIds->has($ownerEmail)) {
                throw new RuntimeException(
                    "Akun pemilik untuk {$row['location']} belum dibuat. Jalankan UserSeeder sebelum UmkmSeeder."
                );
            }

            $photoUrl = $row['photo_url'];
            unset($row['photo_url']);

            $umkm = Umkm::withTrashed()->firstOrNew([
                'name' => $row['name'],
                'location' => $row['location'],
            ]);
            $umkm->fill([
                ...$row,
                'owner_id' => $ownerIds[$ownerEmail],
                'img_label' => null,
                'list_label' => null,
                'verification' => 'disetujui',
                'hidden' => false,
            ]);
            $umkm->forceFill(['deleted_at' => null])->save();

            if ($photoUrl !== null) {
                $umkm->photos()->firstOrCreate(
                    ['url' => $photoUrl],
                    ['disk' => null, 'path' => null, 'sort_order' => 0],
                );
            }
        }
    }

    private function removeLegacyDemoData(): void
    {
        $legacy = Umkm::withTrashed()
            ->where(function (Builder $query): void {
                foreach (self::LEGACY_DEMO_UMKMS as $id => $name) {
                    $query->orWhere(fn (Builder $match) => $match
                        ->whereKey($id)
                        ->where('name', $name));
                }
            })
            ->get();

        if ($legacy->isEmpty()) {
            return;
        }

        $ids = $legacy->modelKeys();

        DB::transaction(function () use ($ids): void {
            DB::table('umkm_items')->whereIn('umkm_id', $ids)->delete();
            Umkm::withTrashed()->whereKey($ids)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Rows 2-7 from the "UMKM" sheet of impor-umkm.xlsx.
     *
     * @return list<array{name: string, category: string, location: string, address: string, hours: string, phone: string, ig: ?string, price_label: string, tag: string, status: string, photo_url: ?string}>
     */
    private function data(): array
    {
        return [
            [
                'name' => 'Warung Contoh Rasa',
                'category' => 'Kuliner',
                'location' => 'Balikpapan Kota',
                'address' => 'Jl. Contoh No. 1',
                'hours' => '08.00 - 21.00 WITA',
                'phone' => '0812-0000-0000',
                'ig' => '@contoh.rasa',
                'price_label' => 'Rp15-50rb',
                'tag' => 'Masakan rumahan khas Balikpapan.',
                'status' => 'aktif',
                'photo_url' => 'https://contoh.com/foto-warung.jpg',
            ],
            [
                'name' => 'Warung Ibu Rusmi',
                'category' => 'Kuliner',
                'location' => 'Balikpapan Utara',
                'address' => 'RT 34',
                'hours' => '09.00 - 20.00 WITA',
                'phone' => '0851-3261-0934',
                'ig' => null,
                'price_label' => 'RP.1-5rb',
                'tag' => 'Salome di Kilo 15',
                'status' => 'aktif',
                'photo_url' => null,
            ],
            [
                'name' => 'Maximal',
                'category' => 'Jasa',
                'location' => 'Balikpapan Utara',
                'address' => 'Jl. Sein Wain Kilo 15',
                'hours' => '09.00 - 21.00 WITA',
                'phone' => '0851-3261-0934',
                'ig' => null,
                'price_label' => 'Relative',
                'tag' => 'Tempat Service HP & Laptop',
                'status' => 'aktif',
                'photo_url' => null,
            ],
            [
                'name' => 'Teh Kita',
                'category' => 'Minuman',
                'location' => 'Balikpapan Utara',
                'address' => 'Jl. Sungai Wain,Kilo 15, RT 33',
                'hours' => '09.00 - 21.00 WITA',
                'phone' => '0882-1661-3626',
                'ig' => null,
                'price_label' => 'Rp.5-13rb',
                'tag' => 'Menjual minuman the, kopi, dan minuman susu',
                'status' => 'aktif',
                'photo_url' => null,
            ],
            [
                'name' => 'Warung Ancah',
                'category' => 'Kuliner',
                'location' => 'Balikpapan Utara',
                'address' => 'Jl.Sungai Wain, Kilo 15, RT 35',
                'hours' => '10.00 - 18.00 WITA',
                'phone' => '0858-4946-0776',
                'ig' => null,
                'price_label' => 'Rp.5-20rb',
                'tag' => 'Warkop murah meriah di kilo 15',
                'status' => 'aktif',
                'photo_url' => null,
            ],
            [
                'name' => 'Warung Ibu Kaya',
                'category' => 'Toko Sayur & Buah',
                'location' => 'Balikpapan Utara',
                'address' => 'Jl. Sein Wain Kilo 15',
                'hours' => '08.00 - 21.00 WITA',
                'phone' => '0896-9147-0689',
                'ig' => null,
                'price_label' => 'Relative',
                'tag' => 'Penjual Sayur dan Sembako di kilo 15 dekat itk',
                'status' => 'aktif',
                'photo_url' => null,
            ],
        ];
    }
}
