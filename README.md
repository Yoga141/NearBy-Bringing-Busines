# INSOS Nearby Database

Direktori UMKM Balikpapan — **NearBy**. Frontend Vue 3 + backend Laravel API, dua proyek terpisah dalam satu folder induk ini.

```
NearBy-Bringing-Busines/
├── frontend/           ← Vue 3 + Vite (tampilan website)
├── nearby-backend/     ← Laravel 13 + Sanctum (REST API)
└── db_required/        ← ekspor database (.sql) untuk di-import manual
```

## Prasyarat

- **XAMPP** (Apache tidak wajib jalan, tapi **MySQL wajib jalan**) — pastikan modul MySQL di XAMPP Control Panel berstatus *Running*.
- **PHP 8.3+** dan **Composer** (biasanya sudah dibawa XAMPP, `C:\xampp\php`).
- **Node.js** (untuk `npm`), disarankan versi 18+.

## 1. Siapkan database

Buat database kosong bernama `nearby_db` (lewat phpMyAdmin) lalu import file yang berada di `db_required\nearby_db.sql`

## 2. Jalankan backend (Laravel)

```bash
cd nearby-backend
composer install
copy .env.example .env        # kalau belum ada file .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

> ⚠️ **Tidak perlu `php artisan migrate`.** Struktur tabel + seluruh data sudah
> lengkap dari import `nearby_db.sql` di langkah 1. **Jangan** jalankan
> `php artisan migrate:fresh` — perintah itu akan **menghapus** data hasil import.
> Seeder (`php artisan db:seed`) juga sengaja dikosongkan; data proyek ini hanya
> berasal dari file `db_required/nearby_db.sql`.

Sebelum menjalankan, pastikan konfigurasi database di `nearby-backend/.env` sesuai dengan MySQL di komputer kamu (nilai default dari `.env.example` biasanya sudah benar):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nearby_db
DB_USERNAME=root
DB_PASSWORD=
```

Backend akan aktif di **http://127.0.0.1:8000** (endpoint API di `http://127.0.0.1:8000/api/...`). Biarkan terminal ini tetap terbuka selama development.

## 3. Jalankan frontend (Vue)

Buka terminal baru:

```bash
cd frontend
npm install
```

Buat file `frontend/.env` (kalau belum ada) berisi:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

Lalu jalankan:

```bash
npm run dev
```

Vite akan menampilkan URL lokalnya di terminal, biasanya **http://localhost:5173** (kalau port itu terpakai proses lain, Vite otomatis pindah ke port berikutnya seperti 5174 — pakai URL persis yang tertulis di terminal).

> Kalau port yang dipakai bukan 5173/5174, tambahkan origin tersebut ke `allowed_origins` di `nearby-backend/config/cors.php` supaya frontend tidak diblokir CORS saat memanggil API.

## 4. Buka websitenya

Kunjungi URL dari langkah 3 di browser (**bukan** port 8000 — itu backend API saja, tidak ada tampilan visualnya).

**Akun demo** (password semua: `password`):

| Email | Role |
|---|---|
| `rizky.p@mail.com` | Pengguna |
| `dewi.umkm@mail.com` | Pemilik UMKM |
| `admin@nearby.id` | Administrator |

Atau pakai tombol "masuk cepat sebagai" di halaman `/login`.

## Menjalankan ulang di lain waktu

Setelah setup pertama kali selesai, cukup jalankan (di dua terminal terpisah):

```bash
# terminal 1
cd nearby-backend && php artisan serve --host=127.0.0.1 --port=8000

# terminal 2
cd frontend && npm run dev
```

Pastikan MySQL di XAMPP sudah *Running* sebelum menjalankan backend.
