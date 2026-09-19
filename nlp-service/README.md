# NearBy Voice NLP

Layanan Python (FastAPI) yang menafsirkan ucapan asisten suara NearBy: teks transkrip masuk, intent + entitas + kalimat jawaban keluar. Data UMKM tetap di backend Laravel.

## Menjalankan (pengembangan)

Butuh tiga proses:

| Proses | Perintah | Port |
| --- | --- | --- |
| NLP Python | `nlp-service\jalankan.bat` (pertama kali otomatis membuat `.venv` dan memasang dependensi) | 8001 |
| Laravel API | `cd backend` lalu `php artisan serve` | 8000 |
| Frontend | `cd frontend` lalu `npm run dev` | 5173 |

Vite meneruskan `/api/voice-nlp` ke port 8001 dan sisa `/api` ke Laravel (lihat `frontend/vite.config.ts`). Buka http://localhost:5173 di Chrome/Edge, sentuh layar atau tekan tombol apa saja, lalu ucapkan *"Oke NearBy, aku butuh makanan di Balikpapan Selatan"*.

Dokumentasi interaktif API: http://127.0.0.1:8001/docs

## API

`POST /api/voice-nlp`

```json
{ "text": "aku butuh makanan di balikpapan selatan", "require_wake": false }
```

```json
{
  "intent": "cari_makanan",
  "action": "cari",
  "location": "balikpapan selatan",
  "message": "Baik, mencari makanan di balikpapan selatan.",
  "wake": false,
  "entities": { "category": "Kuliner", "location": "Balikpapan Selatan", "page": null, "keyword": "", "index": null },
  "text": "aku butuh makanan di balikpapan selatan"
}
```

- `require_wake: true` dikirim saat asisten siaga. Ucapan tanpa "Oke NearBy" dijawab `action: "abaikan"`, dan kata bangun saja dijawab `action: "siaga_perintah"`.
- `action` adalah perintah yang dijalankan frontend: `cari`, `buka`, `daftar`, `halaman`, `favorit`, `ulangi`, `berhenti`, `bantuan`, `beranda`, `tidak_dikenal`, `siaga_perintah`, `abaikan`.
- `intent` adalah versi terperinci untuk dibaca manusia, misalnya `cari_makanan`, `cari_penginapan`, `buka_detail`, atau `buka_halaman_panduan`.

## Cara kerja

Berbasis aturan (`nlp.py`): normalisasi, koreksi salah dengar dengan fuzzy matching (`selatn` → `selatan`), deteksi kata bangun, ekstraksi kategori, kecamatan, halaman, dan nomor, lalu penentuan intent secara berurutan. spaCy belum dipakai karena tidak ada model bahasa Indonesia siap pakai yang mengenal kecamatan Balikpapan atau kategori UMKM. Model statistik baru layak dilatih setelah ada kumpulan ucapan pengguna sungguhan.

Kalau layanan ini mati atau lambat (lebih dari 4 detik), frontend otomatis memakai parser lokal `frontend/src/lib/voiceIntent.ts` supaya asisten tidak pernah diam. Tabel kata kunci kedua file sebaiknya diubah bersamaan.

## Tes

```
.venv\Scripts\python -m unittest -v
```

## Produksi

Hosting cPanel saat ini hanya menyalin PHP dan frontend (`.cpanel.yml`). Layanan ini perlu dijalankan terpisah, misalnya lewat "Setup Python App" di cPanel atau di VPS. Build frontend dengan `VITE_VOICE_NLP_URL=https://alamat-layanan/api/voice-nlp`, lalu batasi CORS dengan `NLP_CORS_ORIGINS=https://domain-anda`. Tanpa itu, asisten di produksi tetap berjalan dengan parser lokal.
