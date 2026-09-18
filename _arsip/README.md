# Arsip kode lama

Isi folder ini **tidak dipakai** oleh aplikasi dan tidak ikut di-deploy
(`.cpanel.yml` hanya menyalin `frontend/dist` dan `backend/`). Dipindahkan ke
sini saat backend dirapikan menjadi satu folder `backend/`, supaya tidak ada
yang hilang tanpa sepengetahuan tim.

| Folder                | Asal                                        | Kenapa diarsipkan |
|-----------------------|---------------------------------------------|-------------------|
| `legacy-php-backend/` | `backend/api`, `backend/helpers`, `backend/Uploads`, `backend/database/schema*.sql` | Backend PHP native lama dengan skema database berbeda (`umkm_profiles`, `auth_tokens`, ...). Frontend sudah sepenuhnya memakai API Laravel (`backend/routes/api.php`). |
| `legacy-frontend/`    | `frontend/stores/auth.js`, `frontend/views/` | Hanya dipakai oleh backend lama di atas dan mengimpor modul yang tidak ada (`@/services/api`); tidak pernah ikut di-build. |

Folder `nearby-backend/` di root repo hanya berisi folder kosong (tiruan path
server `/home/nearby/nearby-backend`), jadi tidak ada isinya yang perlu diarsipkan.

Setelah tim yakin tidak membutuhkannya, folder `_arsip/` boleh dihapus.
