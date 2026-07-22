-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 22 Jul 2026 pada 13.44
-- Versi server: 11.4.10-MariaDB-cll-lve
-- Versi PHP: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nearby_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `favorites`
--

CREATE TABLE `favorites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `umkm_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `umkm_id`, `created_at`, `updated_at`) VALUES
(1, 8, 4, '2026-07-22 08:21:50', '2026-07-22 08:21:50'),
(2, 8, 1, '2026-07-22 08:21:51', '2026-07-22 08:21:51');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_07_11_084510_create_personal_access_tokens_table', 1),
(5, '2026_07_11_100000_add_profile_fields_to_users_table', 1),
(6, '2026_07_11_100001_create_umkms_table', 1),
(7, '2026_07_11_100002_create_umkm_items_table', 1),
(8, '2026_07_11_100003_create_reviews_table', 1),
(9, '2026_07_11_100004_create_favorites_table', 1),
(10, '2026_07_11_100005_create_submissions_table', 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, 'App\\Models\\User', 7, 'api', '0763b6a9e6a79802de7c9d03c1b828591c022bfbfff76050ea93ff0861efb514', '[\"*\"]', '2026-07-21 18:23:30', NULL, '2026-07-21 18:23:29', '2026-07-21 18:23:30'),
(6, 'App\\Models\\User', 8, 'api', 'fd63941e412a3f1cc11c307bd5f067e13c5ffa47c090e4e72e3b8b5c508b4161', '[\"*\"]', '2026-07-22 09:09:01', NULL, '2026-07-22 09:09:01', '2026-07-22 09:09:01'),
(7, 'App\\Models\\User', 2, 'api', '3fcec329ae88e785d3f87cc8328652b8b0c37e0c2f15df0618cf599a65b0becf', '[\"*\"]', '2026-07-22 09:25:15', NULL, '2026-07-22 09:25:15', '2026-07-22 09:25:15'),
(12, 'App\\Models\\User', 6, 'api', 'eed7b03ad89df4d6b0be44b7a53bdd67e2ea1a12498e221154f808b03c97c89c', '[\"*\"]', '2026-07-22 09:35:17', NULL, '2026-07-22 09:32:40', '2026-07-22 09:35:17');

-- --------------------------------------------------------

--
-- Struktur dari tabel `platform_monthly_stats`
--

CREATE TABLE `platform_monthly_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `period` char(7) NOT NULL COMMENT 'Format YYYY-MM',
  `umkm_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `user_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `review_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `platform_monthly_stats`
--

INSERT INTO `platform_monthly_stats` (`id`, `period`, `umkm_count`, `user_count`, `review_count`, `created_at`, `updated_at`) VALUES
(1, '2026-02', 18, 40, 210, '2026-02-28 22:59:00', '2026-02-28 22:59:00'),
(2, '2026-03', 24, 58, 480, '2026-03-31 21:59:00', '2026-03-31 21:59:00'),
(3, '2026-04', 29, 79, 760, '2026-04-30 21:59:00', '2026-04-30 21:59:00'),
(4, '2026-05', 33, 96, 1050, '2026-05-31 21:59:00', '2026-05-31 21:59:00'),
(5, '2026-06', 38, 112, 1380, '2026-06-30 21:59:00', '2026-06-30 21:59:00'),
(6, '2026-07', 42, 128, 1642, '2026-07-22 21:59:00', '2026-07-22 21:59:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `problem_reports`
--

CREATE TABLE `problem_reports` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reporter_name` varchar(255) DEFAULT NULL,
  `kind` enum('Tampilan / UI','Fungsi tidak jalan','Data UMKM salah','Login / akun','Lainnya') NOT NULL DEFAULT 'Lainnya',
  `text` text NOT NULL,
  `status` enum('Baru','Ditinjau','Selesai') NOT NULL DEFAULT 'Baru',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `problem_reports`
--

INSERT INTO `problem_reports` (`id`, `user_id`, `reporter_name`, `kind`, `text`, `status`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Rizky Pratama', 'Fungsi tidak jalan', 'Tombol \"Simpan favorit\" di halaman detail UMKM tidak merespons saat diklik dari HP.', 'Baru', '2026-07-22 03:00:00', '2026-07-22 03:00:00'),
(2, NULL, 'Maya Sari', 'Data UMKM salah', 'Nomor telepon Warung Kepiting Kenari yang tertera sudah tidak aktif, seharusnya diganti dengan yang baru.', 'Ditinjau', '2026-07-21 05:00:00', '2026-07-21 05:00:00'),
(3, NULL, 'Andi Wijaya', 'Tampilan / UI', 'Teks di kartu kategori kepotong di layar HP saya (Balikpapan Timur).', 'Baru', '2026-07-20 07:00:00', '2026-07-20 07:00:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `umkm_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `stars` tinyint(3) UNSIGNED NOT NULL,
  `text` text DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `reviews`
--

INSERT INTO `reviews` (`id`, `umkm_id`, `user_id`, `author_name`, `stars`, `text`, `reply`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(2, 1, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(3, 1, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(4, 2, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(5, 2, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(6, 2, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(7, 3, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(8, 3, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(9, 3, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(10, 4, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(11, 4, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(12, 4, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(13, 5, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(14, 5, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(15, 5, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(16, 6, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(17, 6, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(18, 6, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(19, 7, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(20, 7, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(21, 7, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(22, 8, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(23, 8, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(24, 8, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(25, 9, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(26, 9, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(27, 9, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09'),
(28, 10, NULL, 'Rani Oktaviani', 5, 'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.', NULL, '2026-07-19 18:16:09', '2026-07-19 18:16:09'),
(29, 10, NULL, 'Bayu Firmansyah', 4, 'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.', NULL, '2026-07-14 18:16:09', '2026-07-14 18:16:09'),
(30, 10, NULL, 'Siti Marlina', 5, 'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!', NULL, '2026-07-07 18:16:09', '2026-07-07 18:16:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `submissions`
--

CREATE TABLE `submissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `umkm_id` bigint(20) UNSIGNED DEFAULT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `category` enum('Kuliner','Penginapan','Fashion','Oleh-Oleh','Jasa') NOT NULL,
  `location` enum('Balikpapan Kota','Balikpapan Utara','Balikpapan Selatan','Balikpapan Timur','Balikpapan Barat','Balikpapan Tengah') NOT NULL,
  `status` enum('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  `checks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checks`)),
  `files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`files`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `submissions`
--

INSERT INTO `submissions` (`id`, `umkm_id`, `owner_id`, `name`, `owner_name`, `category`, `location`, `status`, `checks`, `files`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, 'Bakso Urat Cak War', 'Suwarno', 'Kuliner', 'Balikpapan Selatan', 'ditolak', '[[\"Deskripsi usaha\",true],[\"Alamat lengkap\",true],[\"Nomor telepon\",true],[\"Foto (min. 3)\",false],[\"Jam operasional\",true],[\"Kategori & wilayah\",true]]', '[{\"name\":\"Tampak depan\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 1.2 MB\"},{\"name\":\"Menu bakso\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 980 KB\"},{\"name\":\"Foto tempat 3\",\"kind\":\"image\",\"ok\":false,\"meta\":\"Belum diunggah\"},{\"name\":\"KTP pemilik\",\"kind\":\"doc\",\"ok\":true,\"meta\":\"PDF \\u00b7 640 KB\"},{\"name\":\"Surat izin usaha (SIUP)\",\"kind\":\"doc\",\"ok\":false,\"meta\":\"Belum diunggah\"}]', '2026-07-21 18:16:09', '2026-07-22 08:27:11'),
(2, NULL, NULL, 'Homestay Bukit Damai', 'Linda Wijaya', 'Penginapan', 'Balikpapan Kota', 'menunggu', '[[\"Deskripsi usaha\",true],[\"Alamat lengkap\",true],[\"Nomor telepon\",true],[\"Foto (min. 3)\",true],[\"Jam operasional\",true],[\"Kategori & wilayah\",true]]', '[{\"name\":\"Tampak depan\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 1.6 MB\"},{\"name\":\"Kamar\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 1.4 MB\"},{\"name\":\"Ruang tamu\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 1.1 MB\"},{\"name\":\"KTP pemilik\",\"kind\":\"doc\",\"ok\":true,\"meta\":\"PDF \\u00b7 720 KB\"},{\"name\":\"Surat izin usaha (SIUP)\",\"kind\":\"doc\",\"ok\":true,\"meta\":\"PDF \\u00b7 810 KB\"}]', '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(3, NULL, NULL, 'Thrift Corner Senja', 'Belum diisi', 'Fashion', 'Balikpapan Utara', 'menunggu', '[[\"Deskripsi usaha\",false],[\"Alamat lengkap\",false],[\"Nomor telepon\",true],[\"Foto (min. 3)\",false],[\"Jam operasional\",false],[\"Kategori & wilayah\",true]]', '[{\"name\":\"Tampak depan\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 900 KB\"},{\"name\":\"Foto koleksi 2\",\"kind\":\"image\",\"ok\":false,\"meta\":\"Belum diunggah\"},{\"name\":\"Foto koleksi 3\",\"kind\":\"image\",\"ok\":false,\"meta\":\"Belum diunggah\"},{\"name\":\"KTP pemilik\",\"kind\":\"doc\",\"ok\":false,\"meta\":\"Belum diunggah\"}]', '2026-07-21 18:16:09', '2026-07-21 18:16:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `umkms`
--

CREATE TABLE `umkms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `owner_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('Kuliner','Penginapan','Fashion','Oleh-Oleh','Jasa') NOT NULL,
  `location` enum('Balikpapan Kota','Balikpapan Utara','Balikpapan Selatan','Balikpapan Timur','Balikpapan Barat','Balikpapan Tengah') NOT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `reviews_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `price_label` varchar(255) DEFAULT NULL,
  `tag` text DEFAULT NULL,
  `img_label` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `hours` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `ig` varchar(255) DEFAULT NULL,
  `list_label` varchar(255) DEFAULT NULL,
  `status` enum('aktif','libur','tutup') NOT NULL DEFAULT 'aktif',
  `verification` enum('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'disetujui',
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `umkms`
--

INSERT INTO `umkms` (`id`, `owner_id`, `name`, `category`, `location`, `rating`, `reviews_count`, `price_label`, `tag`, `img_label`, `address`, `hours`, `phone`, `ig`, `list_label`, `status`, `verification`, `views`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 2, 'Warung Kepiting Kenari', 'Kuliner', 'Balikpapan Timur', 4.8, 213, 'Rp25–75rb', 'Seafood kepiting soka & lada hitam legendaris, resep turun-temurun.', 'foto kepiting', 'Jl. Manunggal No. 12, Balikpapan Timur', '10.00 – 22.00 WITA', '0812-5544-1122', '@kepiting.kenari', 'Menu andalan', 'aktif', 'disetujui', 3, NULL, '2026-07-21 18:16:09', '2026-07-22 09:25:21'),
(2, NULL, 'Kopi Saluang', 'Kuliner', 'Balikpapan Tengah', 4.7, 156, 'Rp15–40rb', 'Kedai kopi robusta lokal dengan suasana hangat khas Kalimantan.', 'foto kedai kopi', 'Jl. Jenderal Sudirman No. 88, Balikpapan Tengah', '08.00 – 23.00 WITA', '0813-4477-9900', '@kopi.saluang', 'Menu andalan', 'aktif', 'disetujui', 0, NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(3, NULL, 'Penginapan Teluk Asri', 'Penginapan', 'Balikpapan Selatan', 4.6, 98, 'Rp180–350rb', 'Homestay nyaman tepi teluk, cocok untuk keluarga dan pekerja.', 'foto kamar', 'Jl. Sepinggan Baru No. 5, Balikpapan Selatan', 'Check-in 14.00 · Check-out 12.00', '0821-5566-3344', '@teluk.asri.stay', 'Tipe kamar', 'aktif', 'disetujui', 0, NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(4, 2, 'Amplang Bahari', 'Oleh-Oleh', 'Balikpapan Utara', 4.9, 321, 'Rp20–60rb', 'Amplang ikan tenggiri renyah, oleh-oleh khas Balikpapan paling dicari.', 'foto amplang', 'Jl. Mulawarman No. 40, Balikpapan Utara', '08.00 – 20.00 WITA', '0852-4488-2211', '@amplang.bahari', 'Produk', 'aktif', 'disetujui', 0, NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(5, NULL, 'Batik Beruang Madu', 'Fashion', 'Balikpapan Kota', 4.5, 74, 'Rp95–450rb', 'Batik tulis & cap motif beruang madu, ikon khas Kota Balikpapan.', 'foto batik', 'Jl. Ahmad Yani No. 21, Balikpapan Tengah', '09.00 – 21.00 WITA', '0819-3322-7788', '@batik.beruangmadu', 'Koleksi', 'aktif', 'disetujui', 0, NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(6, NULL, 'Servis Motor Pak Gultom', 'Jasa', 'Balikpapan Barat', 4.7, 142, 'Mulai Rp30rb', 'Bengkel motor tepercaya, servis cepat & suku cadang lengkap.', 'foto bengkel', 'Jl. Marsma Iswahyudi No. 9, Balikpapan Barat', '08.00 – 18.00 WITA', '0811-2299-6655', '@gultom.motor', 'Layanan', 'aktif', 'disetujui', 0, NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(7, NULL, 'Nasi Kuning Sambal Raja', 'Kuliner', 'Balikpapan Utara', 4.8, 265, 'Rp12–25rb', 'Nasi kuning legendaris pagi hari dengan sambal khas yang menggugah.', 'foto nasi kuning', 'Jl. Pupuk Raya No. 3, Balikpapan Utara', '05.00 – 11.00 WITA', '0857-9900-1234', '@sambalraja.bpn', 'Menu andalan', 'aktif', 'disetujui', 1, NULL, '2026-07-21 18:16:09', '2026-07-22 08:27:38'),
(8, NULL, 'Kriya Rotan Manggar', 'Oleh-Oleh', 'Balikpapan Timur', 4.6, 58, 'Rp45–300rb', 'Kerajinan rotan handmade — tas, keranjang, dan dekorasi rumah.', 'foto kerajinan', 'Jl. Mulawarman, Manggar, Balikpapan Timur', '09.00 – 17.00 WITA', '0813-7788-4455', '@kriya.manggar', 'Produk', 'aktif', 'disetujui', 0, NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(9, NULL, 'Wisma Somber Stay', 'Penginapan', 'Balikpapan Utara', 4.4, 41, 'Rp150–280rb', 'Penginapan bersih dekat pelabuhan Somber, praktis untuk transit.', 'foto wisma', 'Jl. Sultan Hasanuddin No. 17, Balikpapan Utara', 'Check-in 13.00 · Check-out 12.00', '0822-3344-5566', '@somber.stay', 'Tipe kamar', 'aktif', 'disetujui', 1, NULL, '2026-07-21 18:16:09', '2026-07-22 09:27:32'),
(10, NULL, 'Laundry Kilat Sepinggan', 'Jasa', 'Balikpapan Selatan', 4.6, 187, 'Rp7rb/kg', 'Laundry express selesai 3 jam, wangi & rapi, antar-jemput tersedia.', 'foto laundry', 'Jl. Marsma R. Iswahyudi No. 55, Balikpapan Selatan', '07.00 – 21.00 WITA', '0812-6677-8899', '@laundrykilat.spg', 'Layanan', 'aktif', 'disetujui', 0, NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `umkm_daily_stats`
--

CREATE TABLE `umkm_daily_stats` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `umkm_id` bigint(20) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `views` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `umkm_daily_stats`
--

INSERT INTO `umkm_daily_stats` (`id`, `umkm_id`, `date`, `views`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-07-16', 48, '2026-07-16 21:59:00', '2026-07-16 21:59:00'),
(2, 1, '2026-07-17', 66, '2026-07-17 21:59:00', '2026-07-17 21:59:00'),
(3, 1, '2026-07-18', 54, '2026-07-18 21:59:00', '2026-07-18 21:59:00'),
(4, 1, '2026-07-19', 82, '2026-07-19 21:59:00', '2026-07-19 21:59:00'),
(5, 1, '2026-07-20', 96, '2026-07-20 21:59:00', '2026-07-20 21:59:00'),
(6, 1, '2026-07-21', 74, '2026-07-21 21:59:00', '2026-07-21 21:59:00'),
(7, 1, '2026-07-22', 88, '2026-07-22 21:59:00', '2026-07-22 21:59:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `umkm_items`
--

CREATE TABLE `umkm_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `umkm_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` varchar(255) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `umkm_items`
--

INSERT INTO `umkm_items` (`id`, `umkm_id`, `name`, `price`, `img`, `available`, `created_at`, `updated_at`) VALUES
(1, 1, 'Kepiting Saus Padang', 'Rp68rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(2, 1, 'Kepiting Lada Hitam', 'Rp72rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(3, 1, 'Udang Galah Bakar', 'Rp55rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(4, 1, 'Cumi Goreng Tepung', 'Rp38rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(5, 2, 'Kopi Susu Saluang', 'Rp22rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(6, 2, 'Robusta Tubruk', 'Rp15rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(7, 2, 'Aren Latte', 'Rp26rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(8, 2, 'Roti Bakar Srikaya', 'Rp18rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(9, 3, 'Standar Twin', 'Rp180rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(10, 3, 'Deluxe AC', 'Rp250rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(11, 3, 'Family Room', 'Rp350rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(12, 3, 'Extra Bed', 'Rp60rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(13, 4, 'Amplang Tenggiri 250gr', 'Rp35rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(14, 4, 'Amplang Pedas 250gr', 'Rp38rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(15, 4, 'Kerupuk Kuku Macan', 'Rp25rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(16, 4, 'Paket Oleh-oleh', 'Rp60rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(17, 5, 'Kemeja Batik Cap', 'Rp150rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(18, 5, 'Kain Batik Tulis', 'Rp450rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(19, 5, 'Dress Motif Madu', 'Rp220rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(20, 5, 'Selendang', 'Rp95rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(21, 6, 'Servis Ringan', 'Rp45rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(22, 6, 'Ganti Oli', 'Rp30rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(23, 6, 'Tune Up', 'Rp90rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(24, 6, 'Servis Rem', 'Rp55rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(25, 7, 'Nasi Kuning Komplit', 'Rp20rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(26, 7, 'Nasi Kuning Ayam', 'Rp25rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(27, 7, 'Nasi Kuning Telur', 'Rp12rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(28, 7, 'Teh Manis Hangat', 'Rp5rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(29, 8, 'Tas Rotan', 'Rp180rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(30, 8, 'Keranjang Anyam', 'Rp75rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(31, 8, 'Tudung Saji', 'Rp45rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(32, 8, 'Kursi Rotan', 'Rp300rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(33, 9, 'Ekonomi Fan', 'Rp150rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(34, 9, 'Standar AC', 'Rp220rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(35, 9, 'Deluxe', 'Rp280rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(36, 9, 'Extra Bed', 'Rp50rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(37, 10, 'Cuci Kering Lipat', 'Rp7rb/kg', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(38, 10, 'Cuci Setrika', 'Rp10rb/kg', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(39, 10, 'Express 3 Jam', 'Rp15rb/kg', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09'),
(40, 10, 'Bed Cover', 'Rp25rb', NULL, 1, '2026-07-21 18:16:09', '2026-07-21 18:16:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','owner','admin') NOT NULL DEFAULT 'user',
  `status` enum('aktif','menunggu','nonaktif') NOT NULL DEFAULT 'aktif',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `email_verified_at`, `password`, `role`, `status`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Rizky Pratama', 'rizky.p@mail.com', '0812-0000-0000', NULL, '$2y$12$tY9XChB4A/DsW7p1tAJA/uI0AK1nrNYStqOTe/G2KBcNVclRDDlpm', 'user', 'aktif', NULL, '2026-07-21 18:16:08', '2026-07-21 18:16:08', NULL),
(2, 'Dewi Anjani', 'dewi.umkm@mail.com', '0812-0000-0000', NULL, '$2y$12$.1IGBFTXIZKnTk5VWQNSqug6NBtlhypnAIDp2tJkSg.agQGc9EEuC', 'owner', 'aktif', NULL, '2026-07-21 18:16:08', '2026-07-21 18:16:08', NULL),
(3, 'Suwarno', 'warkop.war@mail.com', '0812-0000-0000', NULL, '$2y$12$4G28dcFh7GKUfzGwzPToS.cbwD1C3WhgpaVkhvsyCteqDCm0F9cn2', 'owner', 'menunggu', NULL, '2026-07-21 18:16:08', '2026-07-21 18:16:08', NULL),
(4, 'Maya Sari', 'maya.s@mail.com', '0812-0000-0000', NULL, '$2y$12$x2w6nDRzloFzjeeWEslcXO2gYysa2EwO4Ilc.HojXClQjnowvpFcW', 'user', 'aktif', NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09', NULL),
(5, 'Bayu Firmansyah', 'bayu.f@mail.com', '0812-0000-0000', NULL, '$2y$12$sMSuH5DJ3YccpBLd5QWgKe.eV1AmY5aHFvX5rj8Maxh9iBUwZvmVu', 'user', 'nonaktif', NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09', NULL),
(6, 'Admin NearBy', 'admin@nearby.id', '0812-0000-0000', NULL, '$2y$12$WXRww5HktKfSyXe9kTMOyO3s/JGwT3VaMhJrmDNdaKMNFNJ0Ld65K', 'admin', 'aktif', NULL, '2026-07-21 18:16:09', '2026-07-21 18:16:09', NULL),
(8, 'user', 'user@gmail.com', NULL, NULL, '$2y$12$BzaVBO6OMyL/Y4MVI7V1yeNGgCB.PCVv5Re6e7D3CRSTq.Lx4PyrS', 'user', 'aktif', NULL, '2026-07-22 08:17:45', '2026-07-22 08:17:45', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indeks untuk tabel `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indeks untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  ADD KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`);

--
-- Indeks untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `favorites_user_id_umkm_id_unique` (`user_id`,`umkm_id`),
  ADD KEY `favorites_umkm_id_foreign` (`umkm_id`);

--
-- Indeks untuk tabel `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indeks untuk tabel `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indeks untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  ADD KEY `personal_access_tokens_expires_at_index` (`expires_at`);

--
-- Indeks untuk tabel `platform_monthly_stats`
--
ALTER TABLE `platform_monthly_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform_monthly_stats_period_unique` (`period`);

--
-- Indeks untuk tabel `problem_reports`
--
ALTER TABLE `problem_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `problem_reports_user_id_foreign` (`user_id`),
  ADD KEY `problem_reports_status_index` (`status`);

--
-- Indeks untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviews_umkm_id_foreign` (`umkm_id`),
  ADD KEY `reviews_user_id_foreign` (`user_id`);

--
-- Indeks untuk tabel `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indeks untuk tabel `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `submissions_umkm_id_foreign` (`umkm_id`),
  ADD KEY `submissions_owner_id_foreign` (`owner_id`);

--
-- Indeks untuk tabel `umkms`
--
ALTER TABLE `umkms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `umkms_owner_id_foreign` (`owner_id`);

--
-- Indeks untuk tabel `umkm_daily_stats`
--
ALTER TABLE `umkm_daily_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `umkm_daily_stats_umkm_date_unique` (`umkm_id`,`date`);

--
-- Indeks untuk tabel `umkm_items`
--
ALTER TABLE `umkm_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `umkm_items_umkm_id_foreign` (`umkm_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `platform_monthly_stats`
--
ALTER TABLE `platform_monthly_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `problem_reports`
--
ALTER TABLE `problem_reports`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `umkms`
--
ALTER TABLE `umkms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `umkm_daily_stats`
--
ALTER TABLE `umkm_daily_stats`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `umkm_items`
--
ALTER TABLE `umkm_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `problem_reports`
--
ALTER TABLE `problem_reports`
  ADD CONSTRAINT `problem_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `submissions_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `umkms`
--
ALTER TABLE `umkms`
  ADD CONSTRAINT `umkms_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `umkm_daily_stats`
--
ALTER TABLE `umkm_daily_stats`
  ADD CONSTRAINT `umkm_daily_stats_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `umkm_items`
--
ALTER TABLE `umkm_items`
  ADD CONSTRAINT `umkm_items_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
