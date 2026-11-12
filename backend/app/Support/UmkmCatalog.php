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
    public const CATEGORIES = [
        'Kuliner', 'Penginapan', 'Fashion', 'Oleh-Oleh', 'Jasa',
        'Minuman', 'Toko Sayur & Buah',
    ];

    public const LOCATIONS = [
        'Balikpapan Kota', 'Balikpapan Utara', 'Balikpapan Selatan',
        'Balikpapan Timur', 'Balikpapan Barat', 'Balikpapan Tengah',
    ];

    public const STATUSES = ['aktif', 'libur', 'tutup'];

    public const VERIFICATIONS = ['menunggu', 'disetujui', 'ditolak'];

    /**
     * Bounding box of Kota Balikpapan (all six kecamatan, with a small margin
     * for the coastline and the Kariangau / Teritip edges). A pin outside it
     * is almost always a typo, swapped lat/lng or a link to another city.
     * Mirrored in the frontend's `src/lib/geo.ts`.
     */
    public const BOUNDS = [
        'lat_min' => -1.42,
        'lat_max' => -0.95,
        'lng_min' => 116.68,
        'lng_max' => 117.15,
    ];

    /**
     * Map any spelling of a kecamatan onto its canonical name:
     * "barat", " Balikpapan  BARAT ", "Kec. Balikpapan Barat", "Bpp Barat"
     * all become "Balikpapan Barat". Unknown text returns null.
     */
    public static function normalizeLocation(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value) ?? $value));
        $text = preg_replace('/^(kecamatan|kec\.?)\s*/u', '', $text) ?? $text;
        $text = preg_replace('/^(balikpapan|bpp|bppn)\s*/u', '', $text) ?? $text;
        $text = trim($text);

        foreach (self::LOCATIONS as $location) {
            $suffix = mb_strtolower(substr($location, strlen('Balikpapan ')));
            if ($text === $suffix) {
                return $location;
            }
        }

        return null;
    }

    /** "Aktif", " AKTIF " -> "aktif"; unknown text returns null. */
    public static function normalizeStatus(mixed $value): ?string
    {
        return self::normalizeFrom($value, self::STATUSES);
    }

    /** "Disetujui", "DISETUJUI " -> "disetujui"; unknown text returns null. */
    public static function normalizeVerification(mixed $value): ?string
    {
        return self::normalizeFrom($value, self::VERIFICATIONS);
    }

    public static function withinBalikpapan(float $lat, float $lng): bool
    {
        return $lat >= self::BOUNDS['lat_min'] && $lat <= self::BOUNDS['lat_max']
            && $lng >= self::BOUNDS['lng_min'] && $lng <= self::BOUNDS['lng_max'];
    }

    /** @param  list<string>  $allowed */
    private static function normalizeFrom(mixed $value, array $allowed): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $text = mb_strtolower(trim($value));

        return in_array($text, $allowed, true) ? $text : null;
    }
}
