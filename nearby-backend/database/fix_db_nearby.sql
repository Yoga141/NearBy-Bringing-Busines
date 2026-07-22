-- =====================================================================
-- NearBy Balikpapan — fix_db_nearby.sql
--
-- Skema database MySQL/MariaDB diturunkan LANGSUNG dari analisis fitur
-- frontend (Vue 3 + Pinia) di frontend/NearBy-Bringing-Busines/src.
-- Setiap tabel di bawah memetakan satu (atau lebih) fitur yang saat ini
-- masih mock/hardcoded/in-memory di frontend dan belum punya persistensi
-- nyata (lihat komentar di atas tiap tabel untuk sumber fiturnya).
--
-- Import via phpMyAdmin, atau:
--   mysql -u root -p < fix_db_nearby.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `nearby_db`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `nearby_db`;

-- --------------------------------------------------------------------
-- users
-- Sumber: stores/auth.ts, LoginView.vue, RegisterView.vue (akun & auth),
--         ProfileEditCard.vue / AccountView.vue (profil),
--         PenggunaTab.vue (manajemen user oleh admin),
--         DeleteAccountTab.vue (trash akun 30 hari via deleted_at)
-- --------------------------------------------------------------------
CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(30) NULL DEFAULT NULL,
    `profile_photo` VARCHAR(255) NULL DEFAULT NULL,
    `role` ENUM('user', 'owner', 'admin') NOT NULL DEFAULT 'user',
    `status` ENUM('aktif', 'menunggu', 'nonaktif') NOT NULL DEFAULT 'aktif',
    `remember_token` VARCHAR(100) NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    KEY `users_role_status_index` (`role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- password_reset_tokens
-- Sumber: ForgotPasswordModal.vue (saat ini cuma alert palsu)
-- --------------------------------------------------------------------
CREATE TABLE `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- user_sessions
-- Sumber: SecurityTab.vue "Sesi aktif" (endSession) — juga dipakai
-- sebagai tabel token autentikasi API (1 baris = 1 device/login).
-- --------------------------------------------------------------------
CREATE TABLE `user_sessions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `token` VARCHAR(100) NOT NULL,
    `device_label` VARCHAR(255) NULL DEFAULT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` VARCHAR(255) NULL DEFAULT NULL,
    `last_active_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_sessions_token_unique` (`token`),
    KEY `user_sessions_user_id_foreign` (`user_id`),
    CONSTRAINT `user_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- user_settings
-- Sumber: PermissionsTab.vue (notifikasi/privasi), SecurityTab.vue (2FA)
-- --------------------------------------------------------------------
CREATE TABLE `user_settings` (
    `user_id` BIGINT UNSIGNED NOT NULL,
    `email_notif` TINYINT(1) NOT NULL DEFAULT 1,
    `push_notif` TINYINT(1) NOT NULL DEFAULT 1,
    `location_access` TINYINT(1) NOT NULL DEFAULT 0,
    `promo_notif` TINYINT(1) NOT NULL DEFAULT 1,
    `data_share` TINYINT(1) NOT NULL DEFAULT 0,
    `twofa_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `user_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- umkms
-- Sumber: data/umkm.ts, stores/umkm.ts, DirectoryView.vue, DetailView.vue,
--         EditUmkmModal.vue (listing + status buka/tutup + visibilitas admin)
-- Kategori & wilayah tetap ENUM (mengikuti union type tetap di frontend,
-- types/index.ts: CategoryName & LocationName — prioritas rendah utk dinamis)
-- --------------------------------------------------------------------
CREATE TABLE `umkms` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `owner_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `category` ENUM('Kuliner', 'Penginapan', 'Fashion', 'Oleh-Oleh', 'Jasa') NOT NULL,
    `location` ENUM(
        'Balikpapan Kota',
        'Balikpapan Utara',
        'Balikpapan Selatan',
        'Balikpapan Timur',
        'Balikpapan Barat',
        'Balikpapan Tengah'
    ) NOT NULL,
    `rating` DECIMAL(2, 1) NOT NULL DEFAULT 0,
    `reviews_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `price_label` VARCHAR(100) NULL DEFAULT NULL,
    `tag` VARCHAR(255) NULL DEFAULT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `address` VARCHAR(255) NULL DEFAULT NULL,
    `hours` VARCHAR(100) NULL DEFAULT NULL,
    `phone` VARCHAR(30) NULL DEFAULT NULL,
    `ig` VARCHAR(100) NULL DEFAULT NULL,
    `status` ENUM('aktif', 'libur', 'tutup') NOT NULL DEFAULT 'aktif',
    `verification` ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu',
    `is_hidden` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'sembunyikan dari publik oleh admin, terpisah dari status/verification',
    `views` INT UNSIGNED NOT NULL DEFAULT 0,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `umkms_owner_id_foreign` (`owner_id`),
    KEY `umkms_category_location_index` (`category`, `location`),
    KEY `umkms_verification_index` (`verification`),
    FULLTEXT KEY `umkms_search_fulltext` (`name`, `tag`, `description`),
    CONSTRAINT `umkms_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- umkm_photos
-- Sumber: EditUmkmModal.vue (3 foto wajib per UMKM, onPhotoFile)
-- --------------------------------------------------------------------
CREATE TABLE `umkm_photos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `umkm_id` BIGINT UNSIGNED NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `position` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `umkm_photos_umkm_id_foreign` (`umkm_id`),
    CONSTRAINT `umkm_photos_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- umkm_items
-- Sumber: EditUmkmModal.vue (daftar menu/produk per UMKM, onMenuFile)
-- --------------------------------------------------------------------
CREATE TABLE `umkm_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `umkm_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `price` VARCHAR(100) NULL DEFAULT NULL,
    `img` VARCHAR(255) NULL DEFAULT NULL,
    `available` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `umkm_items_umkm_id_foreign` (`umkm_id`),
    CONSTRAINT `umkm_items_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- reviews
-- Sumber: data/reviews.ts, stores/reviews.ts, ReviewForm.vue (ulasan +
--         lampiran media), ReplyReviewModal.vue (balasan owner),
--         HistoryTab.vue (riwayat komentar — disatukan ke sini sebagai
--         satu-satunya source of truth, bukan array terpisah)
-- --------------------------------------------------------------------
CREATE TABLE `reviews` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `umkm_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `stars` TINYINT UNSIGNED NOT NULL,
    `text` TEXT NULL DEFAULT NULL,
    `media` JSON NULL DEFAULT NULL COMMENT 'array url foto/video lampiran ulasan',
    `reply` TEXT NULL DEFAULT NULL,
    `replied_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `reviews_umkm_id_foreign` (`umkm_id`),
    KEY `reviews_user_id_foreign` (`user_id`),
    CONSTRAINT `reviews_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `reviews_stars_check` CHECK (`stars` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- favorites (pivot users <-> umkms)
-- Sumber: stores/umkm.ts (favorites, toggleFavorite), FavoritesView.vue
-- --------------------------------------------------------------------
CREATE TABLE `favorites` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `umkm_id` BIGINT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `favorites_user_id_umkm_id_unique` (`user_id`, `umkm_id`),
    KEY `favorites_umkm_id_foreign` (`umkm_id`),
    CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `favorites_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- submissions
-- Sumber: dashboardSeed.ts (SUBMISSIONS_RAW), VerifikasiTab.vue,
--         SubmissionDetailModal.vue (approveSubmission/requestFix/rejectSubmission)
-- --------------------------------------------------------------------
CREATE TABLE `submissions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `umkm_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `owner_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `owner_name` VARCHAR(255) NULL DEFAULT NULL,
    `category` ENUM('Kuliner', 'Penginapan', 'Fashion', 'Oleh-Oleh', 'Jasa') NOT NULL,
    `location` ENUM(
        'Balikpapan Kota',
        'Balikpapan Utara',
        'Balikpapan Selatan',
        'Balikpapan Timur',
        'Balikpapan Barat',
        'Balikpapan Tengah'
    ) NOT NULL,
    `status` ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu',
    `checks` JSON NULL DEFAULT NULL COMMENT 'checklist kelengkapan data',
    `files` JSON NULL DEFAULT NULL COMMENT 'daftar dokumen/foto pendukung',
    `admin_note` TEXT NULL DEFAULT NULL COMMENT 'catatan admin: alasan tolak / minta perbaikan data',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `submissions_umkm_id_foreign` (`umkm_id`),
    KEY `submissions_owner_id_foreign` (`owner_id`),
    KEY `submissions_status_index` (`status`),
    CONSTRAINT `submissions_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE SET NULL,
    CONSTRAINT `submissions_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- problem_reports
-- Sumber: dashboardSeed.ts (PROBLEM_REPORTS_RAW), HelpWidget.vue tab
--         "Laporkan", LaporanMasalahTab.vue (setProblemReportStatus)
-- --------------------------------------------------------------------
CREATE TABLE `problem_reports` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `reporter_name` VARCHAR(255) NULL DEFAULT NULL,
    `kind` VARCHAR(100) NULL DEFAULT NULL COMMENT 'jenis masalah, mis. Bug/Tampilan/Data salah',
    `text` TEXT NOT NULL,
    `status` ENUM('baru', 'ditinjau', 'selesai') NOT NULL DEFAULT 'baru',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `problem_reports_user_id_foreign` (`user_id`),
    KEY `problem_reports_status_index` (`status`),
    CONSTRAINT `problem_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- support_questions
-- Sumber: HelpWidget.vue tab "Bertanya" (submitAsk — saat ini cuma alert)
-- --------------------------------------------------------------------
CREATE TABLE `support_questions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `question` TEXT NOT NULL,
    `answer` TEXT NULL DEFAULT NULL,
    `status` ENUM('baru', 'dijawab') NOT NULL DEFAULT 'baru',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `support_questions_user_id_foreign` (`user_id`),
    CONSTRAINT `support_questions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- umkm_views
-- Sumber: dashboardSeed.ts (DASH_STATS/CHART_BARS/ADMIN_STATS — statistik
--         palsu di RingkasanTab.vue & LaporanTab.vue) dan HistoryTab.vue
--         (visitHistory statis) — log ini jadi sumber agregasi nyata utk
--         keduanya (statistik kunjungan & riwayat kunjungan per user)
-- --------------------------------------------------------------------
CREATE TABLE `umkm_views` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `umkm_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `viewed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `umkm_views_umkm_id_foreign` (`umkm_id`),
    KEY `umkm_views_user_id_foreign` (`user_id`),
    KEY `umkm_views_viewed_at_index` (`viewed_at`),
    CONSTRAINT `umkm_views_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE,
    CONSTRAINT `umkm_views_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
