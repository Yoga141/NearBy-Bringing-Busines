<?php

namespace App\Support;

/**
 * The fixed vocabularies of a UMKM row.
 *
 * These mirror the enum columns in the `umkms` migration and the frontend's
 * `src/data/categories.ts`, and used to be copy-pasted into every controller
 * that validated them. One source keeps the API, the Excel import and the
 * validation messages from drifting apart.
 */
final class UmkmCatalog
{
    public const CATEGORIES = ['Kuliner', 'Penginapan', 'Fashion', 'Oleh-Oleh', 'Jasa'];

    public const LOCATIONS = [
        'Balikpapan Kota', 'Balikpapan Utara', 'Balikpapan Selatan',
        'Balikpapan Timur', 'Balikpapan Barat', 'Balikpapan Tengah',
    ];

    public const STATUSES = ['aktif', 'libur', 'tutup'];

    public const VERIFICATIONS = ['menunggu', 'disetujui', 'ditolak'];
}
