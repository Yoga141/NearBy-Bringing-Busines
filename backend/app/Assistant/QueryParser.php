<?php

namespace App\Assistant;

use App\Support\UmkmCatalog;

/**
 * Reads one Indonesian chat message into search criteria.
 *
 * Deterministic on purpose: what the user asked for decides the database
 * query, so the list of UMKM is always the real one - never something a
 * language model imagined. The vocabulary mirrors the voice assistant's
 * (`frontend/src/lib/voiceIntent.ts`, PERINTAH_SUARA.md).
 */
final class QueryParser
{
    /** Words that point at a category. Multi-word phrases are matched as phrases. */
    private const CATEGORY_WORDS = [
        'Kuliner' => [
            'kuliner', 'makan', 'makanan', 'restoran', 'resto', 'rumah makan', 'warung', 'warteg', 'cafe',
            'kafe', 'kopi', 'ngopi', 'bakso', 'soto', 'sate', 'nasi', 'ayam', 'seafood', 'kepiting', 'ikan',
            'minuman', 'minum', 'jajanan', 'jajan', 'kue', 'roti', 'martabak', 'lapar', 'sarapan', 'mie',
            'kuliner', 'camilan', 'cemilan', 'udang', 'cumi',
        ],
        'Penginapan' => [
            'penginapan', 'hotel', 'losmen', 'homestay', 'wisma', 'guest house', 'guesthouse', 'kos',
            'kosan', 'kost', 'menginap', 'nginap', 'inap', 'kamar',
        ],
        'Fashion' => [
            'fashion', 'baju', 'pakaian', 'busana', 'kaos', 'kemeja', 'batik', 'sepatu', 'sandal', 'tas',
            'hijab', 'jilbab', 'konveksi', 'distro', 'butik', 'dress', 'kain',
        ],
        'Oleh-Oleh' => [
            'oleh oleh', 'oleholeh', 'buah tangan', 'cinderamata', 'souvenir', 'suvenir', 'kerajinan',
            'amplang', 'keripik', 'kerupuk', 'rotan', 'kriya',
        ],
        'Jasa' => [
            'jasa', 'layanan', 'potong rambut', 'cukur', 'cuci mobil', 'cuci motor', 'servis', 'service',
            'bengkel', 'laundry', 'binatu', 'salon', 'barbershop', 'reparasi', 'percetakan', 'fotokopi',
            'tambal ban', 'montir',
        ],
    ];

    /** Category words too generic to also be a search term. */
    private const GENERIC_WORDS = [
        'kuliner', 'makan', 'makanan', 'restoran', 'resto', 'rumah makan', 'minuman', 'minum', 'jajanan',
        'jajan', 'lapar', 'penginapan', 'menginap', 'nginap', 'inap', 'fashion', 'pakaian', 'busana',
        'oleh oleh', 'oleholeh', 'buah tangan', 'jasa', 'layanan', 'warung', 'camilan', 'cemilan',
    ];

    private const LOCATION_WORDS = [
        'balikpapan kota' => 'Balikpapan Kota', 'pusat kota' => 'Balikpapan Kota',
        'utara' => 'Balikpapan Utara', 'selatan' => 'Balikpapan Selatan', 'timur' => 'Balikpapan Timur',
        'barat' => 'Balikpapan Barat', 'tengah' => 'Balikpapan Tengah',
    ];

    /**
     * Places people navigate by. The UMKM table has no coordinates, so "dekat X"
     * matches the address text first and otherwise falls back to the kecamatan
     * X lies in - and the answer says which of the two happened.
     */
    public const LANDMARKS = [
        'kampus' => [
            'label' => 'kampus (ITK / Poltekba)', 'location' => 'Balikpapan Utara',
            'words' => ['kampus', 'itk', 'poltekba', 'kuliah', 'mahasiswa', 'universitas', 'institut teknologi kalimantan'],
            'terms' => ['kampus', 'itk', 'poltekba', 'karang joang', 'soekarno hatta'],
        ],
        'uniba' => [
            'label' => 'Universitas Balikpapan', 'location' => 'Balikpapan Selatan',
            'words' => ['uniba', 'universitas balikpapan'],
            'terms' => ['uniba', 'universitas balikpapan'],
        ],
        'bandara' => [
            'label' => 'Bandara Sepinggan', 'location' => 'Balikpapan Selatan',
            'words' => ['bandara', 'airport', 'sepinggan'],
            'terms' => ['bandara', 'sepinggan'],
        ],
        'semayang' => [
            'label' => 'Pelabuhan Semayang', 'location' => 'Balikpapan Kota',
            'words' => ['semayang', 'pelabuhan semayang'],
            'terms' => ['semayang'],
        ],
        'somber' => [
            'label' => 'Pelabuhan Somber', 'location' => 'Balikpapan Utara',
            'words' => ['somber'],
            'terms' => ['somber'],
        ],
        'manggar' => [
            'label' => 'Pantai Manggar', 'location' => 'Balikpapan Timur',
            'words' => ['manggar', 'lamaru', 'pantai manggar'],
            'terms' => ['manggar', 'lamaru'],
        ],
    ];

    private const SORT_WORDS = [
        'price_asc' => ['murah', 'termurah', 'lebih murah', 'terjangkau', 'hemat', 'ekonomis', 'ramah kantong', 'budget', 'harga miring', 'tidak mahal', 'nggak mahal', 'gak mahal'],
        'price_desc' => ['mahal', 'termahal', 'premium', 'mewah', 'eksklusif'],
        'rating' => ['terbaik', 'paling bagus', 'terbagus', 'rating tinggi', 'rating tertinggi', 'bintang', 'rekomendasi', 'rekomendasikan', 'recommended', 'enak', 'paling enak', 'favorit'],
        'popular' => ['populer', 'terpopuler', 'ramai', 'rame', 'paling banyak dilihat', 'banyak dikunjungi', 'viral', 'terkenal'],
    ];

    private const ORDINALS = [
        'pertama' => 1, 'satu' => 1, 'kesatu' => 1, 'kedua' => 2, 'dua' => 2, 'ketiga' => 3, 'tiga' => 3,
        'keempat' => 4, 'empat' => 4, 'kelima' => 5, 'lima' => 5,
    ];

    /** Filler that never becomes a search term. */
    private const STOPWORDS = [
        'carikan', 'cari', 'mencari', 'cariin', 'tolong', 'dong', 'donk', 'ya', 'yah', 'aku', 'saya', 'gue', 'gw',
        'kami', 'kita', 'mau', 'ingin', 'pengen', 'pingin', 'butuh', 'perlu', 'ada', 'apa', 'adakah', 'yang',
        'di', 'ke', 'dari', 'dan', 'atau', 'untuk', 'buat', 'dengan', 'sama', 'deket', 'dekat', 'sekitar',
        'daerah', 'area', 'kawasan', 'wilayah', 'kecamatan', 'balikpapan', 'kota', 'umkm', 'usaha', 'tempat',
        'toko', 'rekomendasi', 'rekomendasikan', 'tunjukkan', 'tampilkan', 'lihat', 'kasih', 'berikan', 'info',
        'informasi', 'tentang', 'bisa', 'nggak', 'gak', 'tidak', 'enggak', 'sih', 'nih', 'itu', 'ini', 'the',
        'halo', 'hai', 'hi', 'oke', 'ok', 'nearby', 'terdekat', 'sini', 'lagi', 'lain', 'lainnya', 'saja',
        'aja', 'juga', 'kalau', 'kalo', 'gimana', 'bagaimana', 'mana', 'dimana', 'yg', 'dgn', 'utk', 'tolongin',
        'harga', 'buka', 'sekarang', 'lebih', 'paling', 'banget', 'sangat', 'semua', 'seluruh', 'mulai', 'baru',
        'daftar', 'list', 'beberapa', 'nomor', 'no', 'detail', 'detil', 'tentang', 'jelaskan', 'bagus',
    ];

    /**
     * @return array{
     *   category: ?string, location: ?string, allLocations: bool, keyword: ?string,
     *   sort: ?string, near: ?string, nearMe: bool, openOnly: bool, more: bool,
     *   reset: bool, index: ?int, greeting: bool, help: bool, text: string
     * }
     */
    public static function parse(string $message): array
    {
        $text = self::normalise($message);
        $padded = ' '.$text.' ';

        $category = null;
        $categoryHits = [];
        foreach (self::CATEGORY_WORDS as $cat => $words) {
            foreach ($words as $word) {
                if (self::has($padded, $word)) {
                    $categoryHits[$cat] = ($categoryHits[$cat] ?? 0) + 1;
                }
            }
        }
        if ($categoryHits) {
            arsort($categoryHits);
            $category = array_key_first($categoryHits);
        }

        $location = null;
        foreach (self::LOCATION_WORDS as $word => $loc) {
            if (self::has($padded, $word)) {
                $location = $loc;
                break;
            }
        }
        $allLocations = ! $location && (
            self::has($padded, 'semua wilayah') || self::has($padded, 'seluruh balikpapan')
            || self::has($padded, 'mana saja') || self::has($padded, 'di balikpapan')
        );

        $near = null;
        foreach (self::LANDMARKS as $key => $landmark) {
            foreach ($landmark['words'] as $word) {
                if (self::has($padded, $word)) {
                    $near = $key;
                    break 2;
                }
            }
        }
        $nearMe = ! $near && (self::has($padded, 'terdekat') || self::has($padded, 'dekat sini')
            || self::has($padded, 'sekitar sini') || self::has($padded, 'dekat saya') || self::has($padded, 'dekat aku'));

        $sort = null;
        foreach (self::SORT_WORDS as $key => $words) {
            foreach ($words as $word) {
                // "tidak mahal" is price_asc, not price_desc: longer phrases first.
                if (self::has($padded, $word)) {
                    $sort = $key;
                    break 2;
                }
            }
        }

        $index = null;
        if (preg_match('/\b(?:nomor|no|urutan|yang ke|ke)\s*(\d{1,2})\b/u', $text, $m)) {
            $index = (int) $m[1];
        } else {
            foreach (self::ORDINALS as $word => $n) {
                if (preg_match('/\b(?:nomor|yang|detail|detil|buka|pilih)\s+'.$word.'\b/u', $text)) {
                    $index = $n;
                    break;
                }
            }
        }

        $keyword = self::keyword($text, $near);

        return [
            'category' => $category,
            'location' => $location,
            'allLocations' => $allLocations,
            'keyword' => $keyword,
            'sort' => $sort,
            'near' => $near,
            'nearMe' => $nearMe,
            'openOnly' => (bool) preg_match('/\b(?:yang|sedang|lagi|masih)\s+buka\b|\bbuka sekarang\b/u', $text),
            'more' => (bool) preg_match('/\b(?:yang lain|lainnya|lebih banyak|selanjutnya|berikutnya)\b/u', $text),
            'reset' => (bool) preg_match('/\b(?:mulai baru|mulai dari awal|reset|ulang dari awal|cari yang lain saja)\b/u', $text),
            'index' => $index,
            'greeting' => (bool) preg_match('/^(?:halo|hai|hi|hello|pagi|siang|sore|malam|assalamualaikum|permisi)\b/u', $text),
            'help' => (bool) preg_match('/\b(?:bantuan|tolong jelaskan cara|bisa apa|cara pakai|help)\b/u', $text),
            'text' => $text,
        ];
    }

    /** Lowercase, "oleh-oleh" → "oleh oleh", punctuation → spaces. */
    public static function normalise(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private static function has(string $padded, string $phrase): bool
    {
        return str_contains($padded, ' '.$phrase.' ');
    }

    /**
     * The specific thing asked for ("kepiting", "kopi susu", "batik tulis"),
     * after removing filler, generic category words, places and sort words.
     */
    private static function keyword(string $text, ?string $near): ?string
    {
        $remove = [
            ...self::GENERIC_WORDS,
            ...array_keys(self::LOCATION_WORDS),
            ...array_merge(...array_values(self::SORT_WORDS)),
            ...array_keys(self::ORDINALS),
            'semua wilayah', 'seluruh balikpapan', 'mana saja',
        ];
        foreach (self::LANDMARKS as $landmark) {
            array_push($remove, ...$landmark['words']);
        }
        // Longest first, so "rumah makan" goes before "makan".
        usort($remove, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $padded = ' '.$text.' ';
        foreach ($remove as $phrase) {
            $padded = str_replace(' '.$phrase.' ', ' ', $padded);
        }

        $words = array_filter(
            explode(' ', trim(preg_replace('/\s+/u', ' ', $padded) ?? '')),
            fn ($w) => $w !== '' && mb_strlen($w) >= 3 && ! in_array($w, self::STOPWORDS, true) && ! ctype_digit($w),
        );

        $keyword = trim(implode(' ', array_slice(array_values($words), 0, 4)));

        return $keyword === '' ? null : mb_substr($keyword, 0, 60);
    }

    /** Guard values coming back from the client in `context`. */
    public static function validCategory(mixed $value): ?string
    {
        return in_array($value, UmkmCatalog::CATEGORIES, true) ? $value : null;
    }

    public static function validLocation(mixed $value): ?string
    {
        return in_array($value, UmkmCatalog::LOCATIONS, true) ? $value : null;
    }

    public static function validSort(mixed $value): ?string
    {
        return is_string($value) && array_key_exists($value, self::SORT_WORDS) ? $value : null;
    }

    public static function validNear(mixed $value): ?string
    {
        return is_string($value) && array_key_exists($value, self::LANDMARKS) ? $value : null;
    }
}
