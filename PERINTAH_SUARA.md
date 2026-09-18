# Perintah Suara - Asisten Suara NearBy

Dokumen ini berisi daftar perintah suara yang dapat dikenali oleh asisten suara NearBy, fitur aksesibilitas untuk pengguna low-vision/tunanetra. Logika pengenalan perintah ada di `frontend/src/lib/voiceIntent.ts` dan penanganannya di `frontend/src/stores/voice.ts`.

## Cara Memicu

Ucapkan dulu kata bangun **"Oke NearBy"**, baru diikuti perintahnya. Variasi pengucapan seperti "ok nearby", "oke nerbi", "hai nearby", "halo nearby" juga dikenali, karena hasil transkrip suara peramban sering tidak konsisten menangkap nama merek berbahasa Inggris.

Contoh: *"Oke NearBy, carikan aku makanan terdekat di Balikpapan Selatan."*

## 1. Mencari UMKM (intent: `cari`)

Kata kunci pemicu: carikan, cari, temukan, tunjukkan, rekomendasikan, rekomendasi, mau, pengen, ingin, butuh, terdekat, dekat, sekitar.

Bisa digabung dengan nama kategori dan/atau lokasi kecamatan.

Contoh:
- "Carikan aku makanan terdekat di Balikpapan Selatan"
- "Aku mau kopi di sekitar sini"
- "Tunjukkan penginapan di Balikpapan Utara"

### Kategori yang dikenali

| Kategori | Kata kunci contoh |
| --- | --- |
| Kuliner | makan, makanan, restoran, warung, warteg, cafe, kafe, kopi, bakso, soto, sate, nasi, ayam, seafood, minuman, jajanan, kue, roti, martabak, lapar |
| Penginapan | hotel, losmen, homestay, wisma, guest house, kosan, kos, kamar sewa |
| Fashion | baju, pakaian, busana, kaos, kemeja, batik, sepatu, sandal, tas, hijab, jilbab, konveksi, distro |
| Oleh-Oleh | oleh oleh, buah tangan, cinderamata, souvenir, kerajinan, amplang, keripik |
| Jasa | potong rambut, cuci mobil, cuci motor, servis, bengkel, laundry, salon, barbershop, reparasi, percetakan, fotokopi |

### Lokasi (kecamatan) yang dikenali

Bisa disebut lengkap atau singkat, kecuali "Balikpapan Kota" yang harus disebut lengkap agar tidak salah tangkap dengan kata "kota" biasa.

- Balikpapan Kota
- Balikpapan Utara / "utara"
- Balikpapan Selatan / "selatan"
- Balikpapan Timur / "timur"
- Balikpapan Barat / "barat"
- Balikpapan Tengah / "tengah"

## 2. Membuka Hasil Tertentu (intent: `buka`)

Menyebutkan nomor urut hasil pencarian untuk membuka detailnya.

Contoh:
- "Buka nomor dua"
- "Detail nomor tiga"
- "Pilih yang pertama"

Angka 1 sampai 20 atau kata bilangan (satu, kedua, ketiga, keempat, dan seterusnya) dikenali.

## 3. Membuka Seluruh Direktori (intent: `daftar`)

Contoh:
- "Daftar UMKM"
- "Semua UMKM"
- "Direktori"
- "Lihat semua UMKM"
- "Tampilkan semua"

## 4. Membuka Halaman Tertentu (intent: `halaman`)

| Halaman | Contoh ucapan |
| --- | --- |
| Panduan | "halaman panduan", "buka panduan", "cara mendaftar UMKM", "cara daftar UMKM" |
| Tentang Kami | "halaman tentang", "tentang kami", "tentang aplikasi", "tentang nearby" |
| Akun | "halaman akun", "akun saya", "profil saya", "pengaturan akun" |
| Kebijakan Privasi | "kebijakan privasi", "halaman privasi", "privasi" |
| Syarat dan Ketentuan | "syarat dan ketentuan", "ketentuan layanan", "halaman ketentuan" |

Catatan: halaman Akun hanya bisa dibuka jika pengguna sudah masuk (login). Jika belum, asisten akan menjawab bahwa halaman ini hanya ada setelah masuk ke akun.

## 5. Favorit (intent: `favorit`)

Contoh: "favorit saya", "favoritku", "yang disimpan", "kesukaan", "simpanan".

Perlu login. Jika belum masuk, asisten akan menjawab bahwa daftar favorit hanya ada setelah masuk ke akun.

## 6. Kontrol Percakapan

| Intent | Kata kunci contoh |
| --- | --- |
| Ulangi | ulangi, ulang, sekali lagi, apa tadi, bacakan lagi |
| Berhenti | berhenti, hentikan, stop, diam, sudah cukup, batal, batalkan |
| Bantuan | bantuan, bantu aku, bantu saya, perintah apa, bisa apa, apa saja, help |
| Beranda | beranda, halaman utama, halaman depan, kembali ke awal, home |

Mengucapkan "bantuan" akan membuat asisten membacakan ringkasan perintah yang tersedia.

## Perintah Tidak Dikenali

Jika ucapan tidak cocok dengan perintah manapun di atas, asisten akan menjawab:

> "Maaf, saya belum mengerti '...'. Ucapkan bantuan untuk mendengar daftar perintah."
