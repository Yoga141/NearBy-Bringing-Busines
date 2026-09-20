<div align="center">

# NearBy Balikpapan

![Vue.js](https://img.shields.io/badge/Vue.js-3.5-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?style=for-the-badge&logo=typescript&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-8.1-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4.3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Pinia](https://img.shields.io/badge/Pinia-3.0-FFD859?style=for-the-badge&logo=pinia&logoColor=black)
![Vue Router](https://img.shields.io/badge/Vue_Router-5.1-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)
![Three.js](https://img.shields.io/badge/Three.js-0.185-000000?style=for-the-badge&logo=threedotjs&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)

</div>

NearBy Balikpapan adalah website yang dirancang untuk membantu masyarakat menemukan dan mengenal Usaha Mikro, Kecil, dan Menengah (UMKM) di Kalimantan Timur. Platform ini bertujuan untuk mendukung digitalisasi UMKM dengan menyediakan informasi usaha yang lengkap, mudah diakses, dan berbasis lokasi.

## Daftar Isi

- [Tujuan Proyek](#-tujuan-proyek)
- [Fitur](#-fitur)
- [Struktur Proyek](#-struktur-proyek)
- [Teknologi](#-teknologi)
- [Keamanan](#-keamanan)
- [Instalasi dan Menjalankan Proyek](#-instalasi-dan-menjalankan-proyek)
- [Menjalankan Pengujian](#-menjalankan-pengujian)
- [Dokumentasi Tambahan](#-dokumentasi-tambahan)
- [Tim Pengembang](#-tim-pengembang)
- [Status Proyek](#-status-proyek)
- [Lisensi](#-lisensi)

## 🎯 Tujuan Proyek

- Mempermudah masyarakat menemukan UMKM di sekitarnya.
- Mendukung promosi dan digitalisasi UMKM lokal.
- Menyediakan informasi UMKM yang akurat dan mudah diakses.
- Meningkatkan eksposur UMKM kepada masyarakat.

## ✨ Fitur

- 🏠 Landing Page
- 📋 Katalog UMKM
- 🔍 Pencarian UMKM
- 📍 Lokasi dan UMKM Terdekat
- ⭐ Rekomendasi UMKM
- 🏪 Detail UMKM
- 👤 Profil UMKM
- 🛠 Dashboard Pemilik UMKM dan Admin
- 📥 Ekspor/Impor data UMKM lewat Excel
- 🎬 Video Medsos (cuplikan Instagram & YouTube, dikelola dari dashboard)
- ♿ Menu Aksesibilitas (asisten suara, bacakan halaman, keyboard virtual) - lihat [PERINTAH_SUARA.md](PERINTAH_SUARA.md)
- 📱 Responsive Design

## 📁 Struktur Proyek

Repositori ini berisi dua bagian utama yang berdiri sendiri-sendiri:

```
NearBy-Bringing-Busines/
├── backend/    Laravel REST API (PHP, autentikasi, database, Excel, penyimpanan berkas)
├── frontend/   Aplikasi Vue 3 + TypeScript (SPA yang dikonsumsi publik)
├── PERINTAH_SUARA.md  Daftar perintah suara asisten aksesibilitas
└── README.md   Dokumen ini
```

`backend/` adalah satu-satunya backend. Backend PHP native lama (`api/`,
`helpers/`, `schema.sql`) dan folder kosong `nearby-backend/` sudah dihapus.

## 🛠 Teknologi

### Frontend (`frontend/`)
- Vue 3 (Composition API)
- TypeScript
- Vite
- Tailwind CSS 4
- Pinia (state management)
- Vue Router
- Three.js (3D graphics)

### Backend (`backend/`)
- PHP 8.3
- Laravel 13
- Laravel Sanctum (autentikasi API berbasis token, berlaku 30 hari)
- Penulis/pembaca `.xlsx` bawaan (tanpa library tambahan) untuk ekspor/impor Excel

### Database
- SQLite (default untuk pengembangan lokal, cukup satu berkas, tanpa server terpisah)
- MySQL (didukung untuk lingkungan produksi, tinggal ubah konfigurasi koneksi)

## Keamanan

- Validasi input pengguna di setiap endpoint API.
- Autentikasi API berbasis token dengan Laravel Sanctum.
- Pembatasan percobaan login/daftar (rate limiting) dan pembatasan akses per peran (admin / pemilik).
- Perlindungan terhadap SQL Injection (Eloquent ORM, tanpa raw query dari input pengguna).
- Perlindungan terhadap Cross Site Scripting (XSS).
- Sanitasi data sebelum disimpan ke database.

## 🚀 Instalasi dan Menjalankan Proyek

Persyaratan: PHP 8.3+, Composer, Node.js 20+, dan npm.

### 1. Clone repository

```bash
git clone <url-repository-ini>
cd NearBy-Bringing-Busines
```

### 2. Backend (Laravel API)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed   # opsional, mengisi data contoh + akun demo (aman diulang)
php artisan serve
```

Akun demo setelah `db:seed`:

| Peran    | Email                | Password        |
|----------|----------------------|-----------------|
| Admin    | `admin@nearby.id`    | `admin12345`    |
| Pemilik  | `pemilik@nearby.id`  | `pemilik12345`  |
| Pemilik  | `pemilik2@nearby.id` | `pemilik12345`  |
| Pengguna | `pengguna@nearby.id` | `pengguna12345` |

Backend API akan berjalan di `http://localhost:8000` secara default. Sesuaikan koneksi database di `backend/.env` bila ingin memakai MySQL alih-alih SQLite bawaan.

### 3. Frontend (Vue 3 + Vite)

```bash
cd frontend
npm install
npm run dev
```

Frontend akan berjalan di alamat lokal yang ditampilkan Vite (biasanya `http://localhost:5173`), dan memanggil backend API di atas.

Untuk build produksi:

```bash
npm run build
```

## 🧪 Menjalankan Pengujian

Backend menggunakan PHPUnit lewat Artisan. `vendor/` yang ikut di repo hanya
berisi paket produksi (karena disalin apa adanya ke server), jadi pasang paket
pengembangan dulu:

```bash
cd backend
composer install      # menambahkan phpunit dkk. ke vendor/
php artisan test
```

> Jangan men-deploy `vendor/` hasil `composer install` di atas. Sebelum deploy,
> kembalikan ke paket produksi saja dengan `composer install --no-dev`.

Frontend melakukan type-check TypeScript sebagai bagian dari proses build:

```bash
cd frontend
npm run build
```

## 📚 Dokumentasi Tambahan

- [PERINTAH_SUARA.md](PERINTAH_SUARA.md) - daftar lengkap perintah suara yang dikenali asisten aksesibilitas.
- [backend/BACKEND_PLAN.md](backend/BACKEND_PLAN.md) - catatan rencana dan desain backend.

## 👥 Tim Pengembang

Proyek ini dikembangkan oleh mahasiswa Institut Teknologi Kalimantan (ITK) dalam rangka pengembangan sistem informasi berbasis web untuk mendukung digitalisasi UMKM melalui mata kuliah Inovasi Sosial.

## 📌 Status Proyek

Development - Proyek masih dalam tahap pengembangan dan penyempurnaan fitur.

## 📄 Lisensi

Proyek ini dibuat untuk tujuan akademik dan pengembangan pembelajaran.
