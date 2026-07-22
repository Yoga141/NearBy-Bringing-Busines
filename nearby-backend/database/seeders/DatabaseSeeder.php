<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal TIDAK diisi lewat seeder.
     *
     * Sumber data proyek ini = db_required/nearby_db.sql, yang di-IMPORT
     * MANUAL ke database oleh developer (lihat SETUP.md). Seeder ini sengaja
     * dibiarkan kosong supaya tidak ada data dummy dan tidak bergantung pada
     * file .sql.
     */
    public function run(): void
    {
        // Kosong — isi database dengan import db_required/nearby_db.sql.
    }
}
