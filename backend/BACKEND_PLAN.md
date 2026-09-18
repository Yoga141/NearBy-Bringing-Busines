# NearBy - Backend API (Laravel)

Backend REST API untuk aplikasi **NearBy**. Frontend Vue-nya ada di folder `frontend/` pada repo yang sama.

> ✅ **Backend sudah dibuat & jalan.** Laravel 13 + Sanctum, database **SQLite**.
> Semua tabel, model, controller, resource, route, dan seeder di dokumen ini
> sudah diimplementasikan. Cara jalan cepat lihat bagian **"Menjalankan"** di bawah.

## Menjalankan

```bash
cd backend
composer install             # tidak perlu kalau vendor/ sudah ada
cp .env.example .env         # sudah pakai SQLite
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve            # http://127.0.0.1:8000
```

`php artisan db:seed` aman dijalankan berulang (idempoten). Untuk hanya
membuat/mereset akun demo: `php artisan db:seed --class=UserSeeder`.

## Akun demo

Dibuat oleh `database/seeders/UserSeeder.php`. Password bisa diganti lewat
`SEED_ADMIN_PASSWORD`, `SEED_OWNER_PASSWORD`, `SEED_USER_PASSWORD` di `.env`
(**wajib** diganti sebelum seeding server yang bisa diakses publik).

| Peran    | Email                | Password        | Keterangan                                   |
|----------|----------------------|-----------------|----------------------------------------------|
| admin    | `admin@nearby.id`    | `admin12345`    | Dashboard admin                              |
| pemilik  | `pemilik@nearby.id`  | `pemilik12345`  | Dewi Anjani - UMKM #1 & #4                   |
| pemilik  | `pemilik2@nearby.id` | `pemilik12345`  | Budi Santoso - UMKM #2 & #7                  |
| pengguna | `pengguna@nearby.id` | `pengguna12345` | Pengunjung biasa                             |

## Struktur kode

| Folder                        | Isi                                                                 |
|-------------------------------|---------------------------------------------------------------------|
| `app/Http/Controllers/Api`    | Controller REST (tipis: validasi → model/service → resource)       |
| `app/Http/Requests/Auth`      | FormRequest login & register (normalisasi email, pesan Indonesia)  |
| `app/Http/Middleware`         | `EnsureUserHasRole` → dipakai sebagai `role:admin`, `role:owner,admin` |
| `app/Excel`                   | Logika export/impor Excel per dataset (`UmkmPorter`, `UmkmItemPorter`) |
| `app/Support/Xlsx`            | Penulis & pembaca `.xlsx` murni PHP (tanpa library tambahan)       |
| `app/Support/UmkmCatalog.php` | Daftar kategori, wilayah, status, verifikasi (satu sumber)         |
| `lang/id`                     | Pesan validasi Bahasa Indonesia                                     |

---

## Skema Database

Diturunkan dari `src/types/index.ts` dan data di `src/data/` pada frontend.

### `users`
| kolom       | tipe                                   | catatan                          |
|-------------|----------------------------------------|----------------------------------|
| id          | bigint PK                              |                                  |
| name        | string                                 |                                  |
| email       | string unique                          |                                  |
| phone       | string nullable                        | `profilePhone` di frontend       |
| password    | string (hashed)                        |                                  |
| role        | enum('user','owner','admin')           | default `user`                   |
| status      | enum('aktif','menunggu','nonaktif')    | default `aktif`                  |
| deleted_at  | timestamp nullable                     | soft delete (fitur Trash)        |
| timestamps  |                                        | `created_at` = tanggal join      |

### `umkms`
| kolom        | tipe                                          | catatan                         |
|--------------|-----------------------------------------------|---------------------------------|
| id           | bigint PK                                     |                                 |
| owner_id     | bigint FK → users.id                          | pemilik UMKM                    |
| name         | string                                        |                                 |
| category     | enum('Kuliner','Penginapan','Fashion','Oleh-Oleh','Jasa') |                     |
| location     | enum(6 wilayah Balikpapan)                    | lihat `LocationName`            |
| rating       | decimal(2,1) default 0                        | dihitung dari reviews           |
| reviews_count| integer default 0                             | cache jumlah ulasan             |
| price_label  | string                                        | mis. "Rp25–75rb"                |
| tag          | text                                          | deskripsi singkat               |
| address      | string                                        |                                 |
| hours        | string                                        | jam buka                        |
| phone        | string                                        |                                 |
| ig           | string nullable                               | instagram                       |
| status       | enum('aktif','libur','tutup')                 | default `aktif`                 |
| verification | enum('menunggu','disetujui','ditolak')        | untuk fitur verifikasi admin    |
| views        | integer default 0                             |                                 |
| deleted_at   | timestamp nullable                            | soft delete                     |
| timestamps   |                                               |                                 |

### `umkm_items`  (menu / produk milik UMKM)
| kolom     | tipe                 | catatan                    |
|-----------|----------------------|----------------------------|
| id        | bigint PK            |                            |
| umkm_id   | bigint FK → umkms.id |                            |
| name      | string               | nama menu/produk           |
| price     | string               | mis. "Rp68rb"              |
| img       | string nullable      | path gambar                |
| available | boolean default true | `avail` di frontend        |

### `reviews`
| kolom      | tipe                 | catatan                        |
|------------|----------------------|--------------------------------|
| id         | bigint PK            |                                |
| umkm_id    | bigint FK → umkms.id |                                |
| user_id    | bigint FK → users.id |                                |
| stars      | tinyint (1–5)        |                                |
| text       | text                 |                                |
| reply      | text nullable        | balasan pemilik (ReplyReview)  |
| timestamps |                      | `created_at` = tanggal ulasan  |

### `favorites`  (pivot user ⇄ umkm)
| kolom   | tipe                 |
|---------|----------------------|
| user_id | bigint FK → users.id |
| umkm_id | bigint FK → umkms.id |

### `submissions`  (pengajuan UMKM baru untuk diverifikasi admin)
| kolom     | tipe                                     | catatan                     |
|-----------|------------------------------------------|-----------------------------|
| id        | bigint PK                                |                             |
| umkm_id   | bigint FK → umkms.id                     |                             |
| owner_id  | bigint FK → users.id                     |                             |
| status    | enum('menunggu','disetujui','ditolak')   |                             |
| checks    | json                                     | daftar syarat & status      |
| files     | json                                     | dokumen/foto yang diunggah  |
| timestamps|                                          |                             |

### `questions`  (pertanyaan dari "Pusat Bantuan → Bertanya")
| kolom      | tipe                                  | catatan                                   |
|------------|---------------------------------------|-------------------------------------------|
| id         | bigint PK                             |                                           |
| user_id    | bigint FK → users.id, nullable        | null bila dikirim tamu                    |
| name       | string nullable                       | nama yang diisi pengirim                  |
| contact    | string nullable                       | WhatsApp/email - tidak ada inbox in-app   |
| text       | text                                  | isi pertanyaan                            |
| answer     | text nullable                         | catatan jawaban admin                     |
| status     | enum('baru','dijawab','ditutup')      | default `baru`                            |
| timestamps |                                       |                                           |

### `social_videos`  (kartu video medsos di beranda)
| kolom      | tipe                             | catatan                                      |
|------------|----------------------------------|----------------------------------------------|
| id         | bigint PK                        |                                              |
| platform   | enum('youtube','instagram')      | menentukan cara tautan di-embed              |
| title      | string                           | judul di bawah kartu                         |
| url        | string nullable                  | tautan share; `null` = kartu masih placeholder |
| sort_order | unsigned int default 0           | urutan kartu di beranda                      |
| active     | boolean default true             | `false` = tidak tampil di endpoint publik    |
| timestamps |                                  |                                              |

`embedUrl` & `thumbnailUrl` tidak disimpan - keduanya diturunkan dari `url`
oleh model (`App\Models\SocialVideo`), supaya admin bisa menempel tautan apa
pun dari tombol *Share* (`watch?v=`, `youtu.be`, Shorts, `/reel/`, `/p/`).

---

## Daftar Endpoint API (`routes/api.php`)

Semua di-prefix `/api`. Yang butuh login ditandai 🔒 (Sanctum token).

### Auth
| Method | Endpoint            | Fungsi                                          |
|--------|---------------------|-------------------------------------------------|
| POST   | `/register`         | Daftar (pilih role user/owner) - maks. 10/jam/IP |
| POST   | `/login`            | Login, balikin token - maks. 5/menit per akun   |
| POST   | `/logout` 🔒        | Logout                         |
| GET    | `/me` 🔒            | Data user yang sedang login    |

### UMKM (publik untuk baca)
| Method | Endpoint            | Fungsi                                    |
|--------|---------------------|-------------------------------------------|
| GET    | `/umkm`             | List + filter `?category=&location=&q=`   |
| GET    | `/umkm/{id}`        | Detail UMKM + menu + ulasan               |
| POST   | `/umkm` 🔒          | Owner ajukan UMKM baru                     |
| PUT    | `/umkm/{id}` 🔒     | Owner edit UMKM miliknya                   |
| DELETE | `/umkm/{id}` 🔒     | Soft delete (masuk Trash)                 |

### Reviews
| Method | Endpoint                     | Fungsi                     |
|--------|------------------------------|----------------------------|
| GET    | `/umkm/{id}/reviews`         | List ulasan sebuah UMKM    |
| POST   | `/umkm/{id}/reviews` 🔒      | User kirim ulasan          |
| POST   | `/reviews/{id}/reply` 🔒     | Owner balas ulasan         |

### Favorites 🔒
| Method | Endpoint              | Fungsi                    |
|--------|-----------------------|---------------------------|
| GET    | `/favorites`          | List favorit user         |
| POST   | `/umkm/{id}/favorite` | Toggle favorit            |

### Pusat Bantuan (publik - tamu pun bisa)
| Method | Endpoint            | Fungsi                                  |
|--------|---------------------|-----------------------------------------|
| POST   | `/problem-reports`  | Kirim laporan masalah / bug             |
| POST   | `/questions`        | Kirim pertanyaan (tab "Bertanya")       |

### Video Medsos (publik untuk baca)
| Method | Endpoint          | Fungsi                                          |
|--------|-------------------|-------------------------------------------------|
| GET    | `/social-videos`  | Kartu video beranda (hanya yang `active`)       |

### Excel Export / Impor 🔒 (role: owner & admin)

Prefix `/umkm-excel` (data UMKM) atau `/umkm-item-excel` (produk). File `.xlsx`
dibuat & dibaca di server (`app/Support/Xlsx`); owner hanya melihat/menulis
datanya sendiri, UMKM baru dari owner otomatis masuk antrian verifikasi.

| Method | Endpoint     | Fungsi                                                         |
|--------|--------------|----------------------------------------------------------------|
| GET    | `/download`  | Unduh data sebagai `.xlsx`                                     |
| GET    | `/template`  | Unduh template kosong + lembar "Petunjuk"                     |
| POST   | `/preview`   | Upload `file` (.xlsx, maks. 5 MB / 2000 baris) → laporan, tanpa menyimpan |
| POST   | `/commit`    | Upload file yang sama → validasi ulang lalu simpan (semua-atau-tidak-sama-sekali) |
| GET    | `/export`    | Data yang sama dalam JSON (untuk klien API)                    |

`/preview` & `/commit` juga menerima JSON `{ "rows": [...] }`.

### Dashboard Owner 🔒 (role: owner & admin)
| Method | Endpoint              | Fungsi                             |
|--------|-----------------------|------------------------------------|
| GET    | `/owner/summary`      | Ringkasan (statistik, chart)       |
| GET    | `/owner/umkm`         | UMKM milik owner                   |
| GET    | `/owner/reviews`      | Ulasan untuk UMKM owner            |

### Dashboard Admin 🔒 (role: admin)
| Method | Endpoint                        | Fungsi                       |
|--------|---------------------------------|------------------------------|
| GET    | `/admin/users`                  | Kelola pengguna              |
| GET    | `/admin/umkm`                   | Semua UMKM                   |
| GET    | `/admin/submissions`            | Antrian verifikasi           |
| POST   | `/admin/submissions/{id}/approve` | Setujui pengajuan          |
| POST   | `/admin/submissions/{id}/reject`  | Tolak pengajuan            |
| GET    | `/admin/reports`                | Laporan/statistik            |
| GET    | `/admin/trash`                  | Item terhapus (user & umkm)  |
| POST   | `/admin/trash/{id}/restore`     | Pulihkan dari trash          |
| POST   | `/admin/users/{id}/toggle-status` | Aktif/nonaktifkan akun     |
| POST   | `/admin/umkm/{id}/toggle-hidden` | Sembunyikan/tampilkan UMKM  |
| GET    | `/admin/problem-reports`        | Laporan masalah dari pengguna |
| POST   | `/admin/problem-reports/{id}/status` | Ubah status laporan    |
| GET    | `/admin/questions`              | Pertanyaan dari help widget  |
| POST   | `/admin/questions/{id}/answer`  | Tulis jawaban (status → Dijawab) |
| POST   | `/admin/questions/{id}/status`  | Ubah status pertanyaan       |
| GET    | `/admin/social-videos`          | Semua slot video (termasuk nonaktif) |
| POST   | `/admin/social-videos`          | Tambah slot video            |
| PUT    | `/admin/social-videos/{id}`     | Ubah platform/judul/tautan/tampil |
| DELETE | `/admin/social-videos/{id}`     | Hapus slot video             |

Tautan yang tidak cocok dengan platform-nya ditolak **422** beserta pesan
berbahasa Indonesia, supaya kartu tidak diam-diam jadi kosong. Mengosongkan
`url` (kirim `""`) mengembalikan kartu ke keadaan placeholder.

---

## CORS (agar frontend Vue bisa akses API)

Frontend Vite dev berjalan di `http://localhost:5173`.
Di `config/cors.php` Laravel, izinkan origin tersebut:

```php
'allowed_origins' => ['http://localhost:5173'],
'supports_credentials' => true,
```

Di frontend, base URL API bisa disimpan di `.env`:
```env
VITE_API_URL=http://127.0.0.1:8000/api
```
