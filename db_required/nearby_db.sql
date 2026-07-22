-- =====================================================================
-- NearBy (Direktori UMKM Balikpapan) — database: nearby_db
-- Skema dibuat ulang dari migrasi Laravel + data awal dari seeder.
--
-- Cara import via phpMyAdmin:
--   1. Buat database kosong bernama `nearby_db` (utf8mb4_unicode_ci).
--   2. Pilih database itu, buka tab Import, unggah file ini, Go.
--
-- Data awal termuat: 6 user, 10 UMKM, 40 item menu, 30 ulasan, 3 pengajuan.
-- Password semua akun demo: "password".
--   rizky.p@mail.com     (user)
--   dewi.umkm@mail.com   (owner — pemilik UMKM #1 & #4)
--   admin@nearby.id      (admin)
-- =====================================================================

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `favorites` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `umkm_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `favorites_user_id_umkm_id_unique` (`user_id`,`umkm_id`),
  KEY `favorites_umkm_id_foreign` (`umkm_id`),
  CONSTRAINT `favorites_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `favorites_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `favorites` WRITE;
/*!40000 ALTER TABLE `favorites` DISABLE KEYS */;
/*!40000 ALTER TABLE `favorites` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_07_11_084510_create_personal_access_tokens_table',1),(5,'2026_07_11_100000_add_profile_fields_to_users_table',1),(6,'2026_07_11_100001_create_umkms_table',1),(7,'2026_07_11_100002_create_umkm_items_table',1),(8,'2026_07_11_100003_create_reviews_table',1),(9,'2026_07_11_100004_create_favorites_table',1),(10,'2026_07_11_100005_create_submissions_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` text NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `umkm_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `stars` tinyint(3) unsigned NOT NULL,
  `text` text DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_umkm_id_foreign` (`umkm_id`),
  KEY `reviews_user_id_foreign` (`user_id`),
  CONSTRAINT `reviews_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(2,1,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(3,1,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(4,2,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(5,2,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(6,2,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(7,3,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(8,3,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(9,3,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(10,4,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(11,4,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(12,4,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(13,5,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(14,5,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(15,5,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(16,6,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(17,6,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(18,6,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(19,7,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(20,7,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(21,7,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(22,8,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(23,8,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(24,8,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(25,9,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(26,9,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(27,9,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09'),(28,10,NULL,'Rani Oktaviani',5,'Pelayanannya ramah banget, rasanya juara! Sudah beberapa kali balik ke sini dan nggak pernah kecewa.',NULL,'2026-07-19 18:16:09','2026-07-19 18:16:09'),(29,10,NULL,'Bayu Firmansyah',4,'Tempatnya nyaman dan bersih. Harga sesuai kualitas, recommended buat warga sekitaran.',NULL,'2026-07-14 18:16:09','2026-07-14 18:16:09'),(30,10,NULL,'Siti Marlina',5,'Salah satu yang terbaik di Balikpapan. Gampang ditemukan lewat NearBy, terima kasih!',NULL,'2026-07-07 18:16:09','2026-07-07 18:16:09');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `submissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `umkm_id` bigint(20) unsigned DEFAULT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `category` enum('Kuliner','Penginapan','Fashion','Oleh-Oleh','Jasa') NOT NULL,
  `location` enum('Balikpapan Kota','Balikpapan Utara','Balikpapan Selatan','Balikpapan Timur','Balikpapan Barat','Balikpapan Tengah') NOT NULL,
  `status` enum('menunggu','disetujui','ditolak') NOT NULL DEFAULT 'menunggu',
  `checks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checks`)),
  `files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`files`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `submissions_umkm_id_foreign` (`umkm_id`),
  KEY `submissions_owner_id_foreign` (`owner_id`),
  CONSTRAINT `submissions_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `submissions_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `submissions` WRITE;
/*!40000 ALTER TABLE `submissions` DISABLE KEYS */;
INSERT INTO `submissions` VALUES (1,NULL,NULL,'Bakso Urat Cak War','Suwarno','Kuliner','Balikpapan Selatan','menunggu','[[\"Deskripsi usaha\",true],[\"Alamat lengkap\",true],[\"Nomor telepon\",true],[\"Foto (min. 3)\",false],[\"Jam operasional\",true],[\"Kategori & wilayah\",true]]','[{\"name\":\"Tampak depan\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 1.2 MB\"},{\"name\":\"Menu bakso\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 980 KB\"},{\"name\":\"Foto tempat 3\",\"kind\":\"image\",\"ok\":false,\"meta\":\"Belum diunggah\"},{\"name\":\"KTP pemilik\",\"kind\":\"doc\",\"ok\":true,\"meta\":\"PDF \\u00b7 640 KB\"},{\"name\":\"Surat izin usaha (SIUP)\",\"kind\":\"doc\",\"ok\":false,\"meta\":\"Belum diunggah\"}]','2026-07-21 18:16:09','2026-07-21 18:16:09'),(2,NULL,NULL,'Homestay Bukit Damai','Linda Wijaya','Penginapan','Balikpapan Kota','menunggu','[[\"Deskripsi usaha\",true],[\"Alamat lengkap\",true],[\"Nomor telepon\",true],[\"Foto (min. 3)\",true],[\"Jam operasional\",true],[\"Kategori & wilayah\",true]]','[{\"name\":\"Tampak depan\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 1.6 MB\"},{\"name\":\"Kamar\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 1.4 MB\"},{\"name\":\"Ruang tamu\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 1.1 MB\"},{\"name\":\"KTP pemilik\",\"kind\":\"doc\",\"ok\":true,\"meta\":\"PDF \\u00b7 720 KB\"},{\"name\":\"Surat izin usaha (SIUP)\",\"kind\":\"doc\",\"ok\":true,\"meta\":\"PDF \\u00b7 810 KB\"}]','2026-07-21 18:16:09','2026-07-21 18:16:09'),(3,NULL,NULL,'Thrift Corner Senja','Belum diisi','Fashion','Balikpapan Utara','menunggu','[[\"Deskripsi usaha\",false],[\"Alamat lengkap\",false],[\"Nomor telepon\",true],[\"Foto (min. 3)\",false],[\"Jam operasional\",false],[\"Kategori & wilayah\",true]]','[{\"name\":\"Tampak depan\",\"kind\":\"image\",\"ok\":true,\"meta\":\"JPG \\u00b7 900 KB\"},{\"name\":\"Foto koleksi 2\",\"kind\":\"image\",\"ok\":false,\"meta\":\"Belum diunggah\"},{\"name\":\"Foto koleksi 3\",\"kind\":\"image\",\"ok\":false,\"meta\":\"Belum diunggah\"},{\"name\":\"KTP pemilik\",\"kind\":\"doc\",\"ok\":false,\"meta\":\"Belum diunggah\"}]','2026-07-21 18:16:09','2026-07-21 18:16:09');
/*!40000 ALTER TABLE `submissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `umkm_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `umkm_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `umkm_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` varchar(255) DEFAULT NULL,
  `img` varchar(255) DEFAULT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `umkm_items_umkm_id_foreign` (`umkm_id`),
  CONSTRAINT `umkm_items_umkm_id_foreign` FOREIGN KEY (`umkm_id`) REFERENCES `umkms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `umkm_items` WRITE;
/*!40000 ALTER TABLE `umkm_items` DISABLE KEYS */;
INSERT INTO `umkm_items` VALUES (1,1,'Kepiting Saus Padang','Rp68rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(2,1,'Kepiting Lada Hitam','Rp72rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(3,1,'Udang Galah Bakar','Rp55rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(4,1,'Cumi Goreng Tepung','Rp38rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(5,2,'Kopi Susu Saluang','Rp22rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(6,2,'Robusta Tubruk','Rp15rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(7,2,'Aren Latte','Rp26rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(8,2,'Roti Bakar Srikaya','Rp18rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(9,3,'Standar Twin','Rp180rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(10,3,'Deluxe AC','Rp250rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(11,3,'Family Room','Rp350rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(12,3,'Extra Bed','Rp60rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(13,4,'Amplang Tenggiri 250gr','Rp35rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(14,4,'Amplang Pedas 250gr','Rp38rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(15,4,'Kerupuk Kuku Macan','Rp25rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(16,4,'Paket Oleh-oleh','Rp60rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(17,5,'Kemeja Batik Cap','Rp150rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(18,5,'Kain Batik Tulis','Rp450rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(19,5,'Dress Motif Madu','Rp220rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(20,5,'Selendang','Rp95rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(21,6,'Servis Ringan','Rp45rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(22,6,'Ganti Oli','Rp30rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(23,6,'Tune Up','Rp90rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(24,6,'Servis Rem','Rp55rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(25,7,'Nasi Kuning Komplit','Rp20rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(26,7,'Nasi Kuning Ayam','Rp25rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(27,7,'Nasi Kuning Telur','Rp12rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(28,7,'Teh Manis Hangat','Rp5rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(29,8,'Tas Rotan','Rp180rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(30,8,'Keranjang Anyam','Rp75rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(31,8,'Tudung Saji','Rp45rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(32,8,'Kursi Rotan','Rp300rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(33,9,'Ekonomi Fan','Rp150rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(34,9,'Standar AC','Rp220rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(35,9,'Deluxe','Rp280rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(36,9,'Extra Bed','Rp50rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(37,10,'Cuci Kering Lipat','Rp7rb/kg',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(38,10,'Cuci Setrika','Rp10rb/kg',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(39,10,'Express 3 Jam','Rp15rb/kg',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(40,10,'Bed Cover','Rp25rb',NULL,1,'2026-07-21 18:16:09','2026-07-21 18:16:09');
/*!40000 ALTER TABLE `umkm_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `umkms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `umkms` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('Kuliner','Penginapan','Fashion','Oleh-Oleh','Jasa') NOT NULL,
  `location` enum('Balikpapan Kota','Balikpapan Utara','Balikpapan Selatan','Balikpapan Timur','Balikpapan Barat','Balikpapan Tengah') NOT NULL,
  `rating` decimal(2,1) NOT NULL DEFAULT 0.0,
  `reviews_count` int(10) unsigned NOT NULL DEFAULT 0,
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
  `views` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `umkms_owner_id_foreign` (`owner_id`),
  CONSTRAINT `umkms_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `umkms` WRITE;
/*!40000 ALTER TABLE `umkms` DISABLE KEYS */;
INSERT INTO `umkms` VALUES (1,2,'Warung Kepiting Kenari','Kuliner','Balikpapan Timur',4.8,213,'Rp25–75rb','Seafood kepiting soka & lada hitam legendaris, resep turun-temurun.','foto kepiting','Jl. Manunggal No. 12, Balikpapan Timur','10.00 – 22.00 WITA','0812-5544-1122','@kepiting.kenari','Menu andalan','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(2,NULL,'Kopi Saluang','Kuliner','Balikpapan Tengah',4.7,156,'Rp15–40rb','Kedai kopi robusta lokal dengan suasana hangat khas Kalimantan.','foto kedai kopi','Jl. Jenderal Sudirman No. 88, Balikpapan Tengah','08.00 – 23.00 WITA','0813-4477-9900','@kopi.saluang','Menu andalan','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(3,NULL,'Penginapan Teluk Asri','Penginapan','Balikpapan Selatan',4.6,98,'Rp180–350rb','Homestay nyaman tepi teluk, cocok untuk keluarga dan pekerja.','foto kamar','Jl. Sepinggan Baru No. 5, Balikpapan Selatan','Check-in 14.00 · Check-out 12.00','0821-5566-3344','@teluk.asri.stay','Tipe kamar','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(4,2,'Amplang Bahari','Oleh-Oleh','Balikpapan Utara',4.9,321,'Rp20–60rb','Amplang ikan tenggiri renyah, oleh-oleh khas Balikpapan paling dicari.','foto amplang','Jl. Mulawarman No. 40, Balikpapan Utara','08.00 – 20.00 WITA','0852-4488-2211','@amplang.bahari','Produk','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(5,NULL,'Batik Beruang Madu','Fashion','Balikpapan Kota',4.5,74,'Rp95–450rb','Batik tulis & cap motif beruang madu, ikon khas Kota Balikpapan.','foto batik','Jl. Ahmad Yani No. 21, Balikpapan Tengah','09.00 – 21.00 WITA','0819-3322-7788','@batik.beruangmadu','Koleksi','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(6,NULL,'Servis Motor Pak Gultom','Jasa','Balikpapan Barat',4.7,142,'Mulai Rp30rb','Bengkel motor tepercaya, servis cepat & suku cadang lengkap.','foto bengkel','Jl. Marsma Iswahyudi No. 9, Balikpapan Barat','08.00 – 18.00 WITA','0811-2299-6655','@gultom.motor','Layanan','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(7,NULL,'Nasi Kuning Sambal Raja','Kuliner','Balikpapan Utara',4.8,265,'Rp12–25rb','Nasi kuning legendaris pagi hari dengan sambal khas yang menggugah.','foto nasi kuning','Jl. Pupuk Raya No. 3, Balikpapan Utara','05.00 – 11.00 WITA','0857-9900-1234','@sambalraja.bpn','Menu andalan','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(8,NULL,'Kriya Rotan Manggar','Oleh-Oleh','Balikpapan Timur',4.6,58,'Rp45–300rb','Kerajinan rotan handmade — tas, keranjang, dan dekorasi rumah.','foto kerajinan','Jl. Mulawarman, Manggar, Balikpapan Timur','09.00 – 17.00 WITA','0813-7788-4455','@kriya.manggar','Produk','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(9,NULL,'Wisma Somber Stay','Penginapan','Balikpapan Utara',4.4,41,'Rp150–280rb','Penginapan bersih dekat pelabuhan Somber, praktis untuk transit.','foto wisma','Jl. Sultan Hasanuddin No. 17, Balikpapan Utara','Check-in 13.00 · Check-out 12.00','0822-3344-5566','@somber.stay','Tipe kamar','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09'),(10,NULL,'Laundry Kilat Sepinggan','Jasa','Balikpapan Selatan',4.6,187,'Rp7rb/kg','Laundry express selesai 3 jam, wangi & rapi, antar-jemput tersedia.','foto laundry','Jl. Marsma R. Iswahyudi No. 55, Balikpapan Selatan','07.00 – 21.00 WITA','0812-6677-8899','@laundrykilat.spg','Layanan','aktif','disetujui',0,NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09');
/*!40000 ALTER TABLE `umkms` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
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
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Rizky Pratama','rizky.p@mail.com','0812-0000-0000',NULL,'$2y$12$tY9XChB4A/DsW7p1tAJA/uI0AK1nrNYStqOTe/G2KBcNVclRDDlpm','user','aktif',NULL,'2026-07-21 18:16:08','2026-07-21 18:16:08',NULL),(2,'Dewi Anjani','dewi.umkm@mail.com','0812-0000-0000',NULL,'$2y$12$.1IGBFTXIZKnTk5VWQNSqug6NBtlhypnAIDp2tJkSg.agQGc9EEuC','owner','aktif',NULL,'2026-07-21 18:16:08','2026-07-21 18:16:08',NULL),(3,'Suwarno','warkop.war@mail.com','0812-0000-0000',NULL,'$2y$12$4G28dcFh7GKUfzGwzPToS.cbwD1C3WhgpaVkhvsyCteqDCm0F9cn2','owner','menunggu',NULL,'2026-07-21 18:16:08','2026-07-21 18:16:08',NULL),(4,'Maya Sari','maya.s@mail.com','0812-0000-0000',NULL,'$2y$12$x2w6nDRzloFzjeeWEslcXO2gYysa2EwO4Ilc.HojXClQjnowvpFcW','user','aktif',NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09',NULL),(5,'Bayu Firmansyah','bayu.f@mail.com','0812-0000-0000',NULL,'$2y$12$sMSuH5DJ3YccpBLd5QWgKe.eV1AmY5aHFvX5rj8Maxh9iBUwZvmVu','user','nonaktif',NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09',NULL),(6,'Admin NearBy','admin@nearby.id','0812-0000-0000',NULL,'$2y$12$WXRww5HktKfSyXe9kTMOyO3s/JGwT3VaMhJrmDNdaKMNFNJ0Ld65K','admin','aktif',NULL,'2026-07-21 18:16:09','2026-07-21 18:16:09',NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

