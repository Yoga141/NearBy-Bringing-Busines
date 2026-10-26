# Deploy & Kesiapan Server

Panduan ringkas menyiapkan server produksi (www.nearbybalikpapan.com). Alur
deploy cPanel lengkap ada di [README.md](README.md#-deploy-ke-cpanel) dan
`.cpanel.yml`.

## 1. Ekstensi PHP yang dibutuhkan

| Ekstensi    | Wajib | Dipakai untuk |
|-------------|-------|---------------|
| `fileinfo`  | Ya    | Mendeteksi tipe berkas upload (foto, video panduan) |
| `pdo_mysql` | Ya    | Koneksi database MySQL |
| `mbstring`  | Ya    | Laravel (teks UTF-8) |
| `openssl`   | Ya    | Enkripsi, `APP_KEY`, HTTPS |
| `gd` / `imagick` | Tidak | Saat ini tidak dipakai (foto disimpan apa adanya) |

> **Gejala fileinfo mati:** upload foto gagal dengan pesan
> *"Server belum mengaktifkan ekstensi PHP fileinfo. Hubungi admin server."*
> (dulu: `Class "finfo" not found`).
>
> Foto UMKM, produk, dan profil tetap bisa diunggah tanpa fileinfo lewat
> mode cadangan (cek ekstensi nama + `getimagesize()`), tetapi **upload video
> panduan tetap butuh fileinfo**. Jadi tetap aktifkan fileinfo.
> Mode cadangan bisa dimatikan dengan `UPLOAD_FILEINFO_FALLBACK=false` di `.env`.

### Mengaktifkan fileinfo di cPanel

1. Masuk cPanel → **Software** → **Select PHP Version** (CloudLinux PHP Selector).
2. Pastikan versi PHP yang dipilih sama dengan yang dipakai domain (lihat juga
   **MultiPHP Manager**).
3. Buka tab **Extensions**, centang **fileinfo** (dan pastikan `pdo_mysql`,
   `mbstring`, `openssl` juga tercentang). Perubahan tersimpan otomatis.
4. Di tab **Options**, cek `upload_max_filesize` minimal `2M` dan
   `post_max_size` lebih besar darinya (mis. `20M`).
5. Kalau domain memakai **MultiPHP INI Editor**, nilai upload juga bisa diatur di sana.

Kalau menu *Select PHP Version* tidak ada (hanya MultiPHP Manager), minta
penyedia hosting mengaktifkan ekstensi `fileinfo` untuk versi PHP akun ini.

### Mengaktifkan fileinfo di VPS

```bash
php -v                                  # cek versi, mis. 8.3
php --ini                               # lokasi php.ini CLI
# Debian/Ubuntu (fileinfo biasanya bawaan php-common):
sudo apt install php8.3-common
sudo phpenmod fileinfo
# Atau edit php.ini (CLI dan FPM, mis. /etc/php/8.3/fpm/php.ini):
#   extension=fileinfo        <- hapus tanda ';' di depannya
sudo systemctl restart php8.3-fpm       # sesuaikan versi
sudo systemctl reload nginx             # atau: sudo systemctl reload apache2
php -m | grep -i fileinfo               # harus muncul "fileinfo"
```

PHP web (FPM) dan PHP CLI memakai php.ini terpisah; ubah keduanya.

## 2. Cek kesiapan server

Jalankan di folder backend (Terminal cPanel atau SSH):

```bash
cd /home/nearby/nearby-backend
php artisan app:check-server
```

Perintah ini menampilkan **OK/GAGAL** untuk:

- ekstensi `fileinfo`, `pdo_mysql`, `mbstring`, `openssl` (dan `gd`/`imagick`, opsional);
- symlink `public_html/storage` → `nearby-backend/storage/app/public`;
- izin tulis `storage/`, `storage/app/public`, `storage/framework`, `storage/logs`, `bootstrap/cache`;
- `upload_max_filesize`, `post_max_size`, `file_uploads`;
- `APP_URL` (harus domain asli, bukan localhost).

Keluar dengan kode 1 bila ada item GAGAL. Folder web bisa ditentukan sendiri:
`php artisan app:check-server --public=/home/nearby/public_html`.

> Nilai php.ini di CLI bisa berbeda dari PHP web. Kalau perintah ini OK tapi
> upload di situs masih gagal, cek versi PHP domain di **MultiPHP Manager**.

## 3. Perbaikan umum

```bash
# symlink foto
ln -sfn /home/nearby/nearby-backend/storage/app/public /home/nearby/public_html/storage
# izin folder
chmod -R 775 /home/nearby/nearby-backend/storage /home/nearby/nearby-backend/bootstrap/cache
# setelah mengubah .env
php artisan config:clear
```
