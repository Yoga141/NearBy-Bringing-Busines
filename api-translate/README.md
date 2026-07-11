# API Translate

REST API terjemahan Indonesia ↔ Inggris yang jalan 100% lokal/offline setelah model diunduh.

Mesin terjemahannya MarianMT / Helsinki-NLP OPUS-MT, jalan langsung di Node.js lewat [`@xenova/transformers`](https://github.com/xenova/transformers.js) (transformers.js + ONNX Runtime). Model diunduh sekali dari Hugging Face, setelah itu tidak ada panggilan jaringan lagi.

Fitur lain: auto-detect bahasa kalau `from` tidak dikirim, menjaga URL/email/Markdown/HTML/emoji/angka supaya tidak rusak saat diterjemahkan, validasi request (Zod), rate limiting, CORS, logging terstruktur (Winston), dan struktur folder yang gampang ditambah bahasa baru tanpa bongkar arsitektur.

## Struktur folder

```
api-translate/
├── src/
│   ├── config/         # env & runtime config 
│   ├── constants/      # kode bahasa, HTTP status, error code
│   ├── models/         # registry: pasangan bahasa -
│   ├── types/          # kontrak TypeScript
│   ├── validators/     # skema Zod
│   ├── middlewares/    # cors, rate limit, error handler, dsb.
│   ├── services/       # model-manager, marian-translation.engine, language-detector, translation.service
│   ├── controllers/    # handler HTTP tipis, panggil service
│   ├── routes/         # endpoint Express
│   ├── utils/          # error class, response formatter, logger, text protector/chunker
│   ├── app.ts
│   └── server.ts
├── scripts/download-models.ts
├── postman/api-translate.postman_collection.json
├── docs/frontend-usage.md
├── Dockerfile / docker-compose.yml
└── .env.example
```

Alur satu request: `routes` → `validate.middleware` (Zod) → `translate.controller` → `translation.service`. Di `translation.service`: validasi panjang/bahasa → deteksi bahasa (kalau `from` kosong) → lindungi token yang tidak boleh diterjemahkan (`text-protector`) → pecah per baris/kalimat (`text-chunker`) → panggil `marian-translation.engine` (yang minta pipeline ke `model-manager`) → gabungkan hasil → kembalikan token yang dilindungi tadi.

Untuk nambah bahasa baru: tambahkan kode bahasanya ke `SUPPORTED_LANGUAGES`/`LANGUAGE_INFO` di `src/constants/languages.constant.ts`, lalu daftarkan model-nya di `src/models/language-model.registry.ts`:

```ts
'en-ja': { modelId: 'Xenova/opus-mt-en-jap', label: 'English -> Japanese' },
'ja-en': { modelId: 'Xenova/opus-mt-jap-en', label: 'Japanese -> English' },
```

Jalankan `npm run download-models` setelahnya. Tidak ada perubahan lain yang dibutuhkan di controller/route/service.

## Instalasi

Butuh Node.js ≥ 18.

```bash
npm install
cp .env.example .env
```

Variabel penting di `.env` (lengkapnya ada di `.env.example`):

- `PORT` (default `3000`), `API_PREFIX` (default `/api/v1`)
- `CORS_ORIGINS` — isi domain website Anda, jangan biarkan `*` di production
- `MODELS_CACHE_DIR` — tempat model ONNX disimpan
- `MODEL_MAX_INPUT_CHARS` (default `5000`) — batas panjang teks per request
- `PRELOAD_LANGUAGE_PAIRS` (default `id-en,en-id`) — model yang dimuat saat startup, kosongkan untuk lazy-load

## Download model

Model diunduh sekali dari Hugging Face (`Xenova/opus-mt-id-en`, `Xenova/opus-mt-en-id`, ~60-80MB masing-masing, versi quantized) lalu disimpan permanen di `MODELS_CACHE_DIR`. Setelah itu API jalan sepenuhnya offline.

Pre-download eksplisit sebelum server pertama kali jalan (disarankan untuk production, supaya request pertama tidak lambat):

```bash
npm run download-models
```

Kalau tidak di-pre-download, model otomatis diunduh saat request pertama yang membutuhkannya (request itu akan lebih lambat, menunggu unduhan selesai).

## Menjalankan

```bash
npm run dev      # development, auto-reload
npm run build && npm start   # production
```

Server jalan di `http://localhost:3000`, endpoint di bawah `/api/v1`. Cek statusnya:

```bash
curl http://localhost:3000/api/v1/health
```

## API

### `POST /api/v1/translate`

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `text` | string | ya | Teks yang diterjemahkan, maks `MODEL_MAX_INPUT_CHARS` karakter |
| `from` | string | tidak | Kode bahasa sumber (`id`/`en`); kalau kosong, dideteksi otomatis |
| `to` | string | ya | Kode bahasa tujuan (`id`/`en`) |

```json
// Request
{ "text": "Halo dunia", "from": "id", "to": "en" }

// Response 200
{
  "success": true,
  "from": "id",
  "to": "en",
  "translatedText": "Hello world",
  "meta": { "requestId": "...", "timestamp": "...", "durationMs": 182 }
}
```

Kalau `from` dihilangkan, response menyertakan field `detectedLanguage` tambahan. Kalau gagal (mis. pasangan bahasa tidak didukung):

```json
{
  "success": false,
  "error": { "code": "UNSUPPORTED_LANGUAGE_PAIR", "message": "Translation from \"id\" to \"ja\" is not supported yet." },
  "meta": { "requestId": "...", "timestamp": "..." }
}
```

### `POST /api/v1/detect`

`{ "text": "Saya suka kucing" }` → `{ "success": true, "language": "id", "meta": {...} }`

### `GET /api/v1/languages`, `GET /api/v1/health`

Daftar bahasa/model yang didukung, dan status server + model (sudah dimuat ke memori atau belum).

### Status code & error code

`400` validasi/bahasa tidak didukung/teks kosong-terlalu panjang, `404` route tidak ada, `422` auto-detect gagal, `429` rate limit, `500` kesalahan server, `504` timeout terjemahan.

`error.code`: `VALIDATION_ERROR`, `UNSUPPORTED_LANGUAGE`, `UNSUPPORTED_LANGUAGE_PAIR`, `LANGUAGE_DETECTION_FAILED`, `TEXT_TOO_LONG`, `EMPTY_TEXT`, `TRANSLATION_FAILED`, `TRANSLATION_TIMEOUT`, `RATE_LIMIT_EXCEEDED`, `NOT_FOUND`, `INTERNAL_ERROR`.

## Soal menjaga format

Sebelum teks masuk ke model, `text-protector.util.ts` menukar URL/email/tag HTML/markdown link & image/kode inline-blok/emoji dengan placeholder numerik, lalu mengembalikannya setelah terjemahan selesai (placeholder numerik dipilih karena tokenizer MarianMT jauh lebih andal meng-copy-utuh angka dibanding kata buatan berisi huruf).

`text-chunker.util.ts` menerjemahkan per baris, jadi baris kosong (pemisah paragraf) tidak pernah disentuh model, dan penanda awal baris (heading `#`, bullet `-`/`*`, list bernomor, blockquote `>`, checkbox) dilepas dulu sebelum diterjemahkan lalu dipasang kembali — struktur Markdown tetap utuh.

## Testing dengan Postman

Import `postman/api-translate.postman_collection.json`, base URL sudah default ke `http://localhost:3000/api/v1`. Atau langsung:

```bash
curl -X POST http://localhost:3000/api/v1/translate \
  -H "Content-Type: application/json" \
  -d '{"text":"Halo dunia","from":"id","to":"en"}'
```

## Pakai dari website (fetch)

```js
async function translateText(text, from, to) {
  const res = await fetch('http://localhost:3000/api/v1/translate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text, from, to }),
  });
  const data = await res.json();
  if (!data.success) throw new Error(data.error.message);
  return data.translatedText;
}

translateText('Halo dunia', 'id', 'en').then(console.log); // "Hello world"
```

Untuk NearBy Balikpapan (frontend Vue.js terpisah dari backend): set `CORS_ORIGINS` ke domain website-nya saja, dan lihat [`docs/frontend-usage.md`](docs/frontend-usage.md) untuk contoh composable Vue, komponen toggle bahasa, dan pola translate konten dari backend/database.

## Deploy

`api-translate` jalan sebagai proses Node sendiri (port sendiri), di-deploy di VPS yang sama dengan frontend/backend, disatukan di satu domain lewat reverse proxy Nginx berbasis path — bukan subdomain terpisah. Jadi di mata browser semuanya 1 origin, tidak perlu urus CORS lintas domain di production.

```bash
npm install && npm run build && npm run download-models
npm install -g pm2
pm2 start dist/server.js --name api-translate
```

atau `docker compose up -d --build`.

Contoh `nginx.conf`:

```nginx
server {
    listen 443 ssl;
    server_name nearbybalikpapan.com www.nearbybalikpapan.com;

    location / {
        root /var/www/nearbybalikpapan-frontend/dist;
        try_files $uri $uri/ /index.html;
    }

    location /translate-api/ {
        proxy_pass http://127.0.0.1:3000/api/v1/;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # location /api/ { proxy_pass http://127.0.0.1:4000/; }  # backend utama, nanti
}
```

`https://nearbybalikpapan.com/translate-api/translate` diteruskan ke `api-translate` di `localhost:3000`. Di frontend, set `VITE_TRANSLATE_API_URL=https://nearbybalikpapan.com/translate-api` untuk production, dan `http://localhost:3000/api/v1` untuk development lokal (beda origin dari Vue dev server, jadi `CORS_ORIGINS` tetap perlu menyertakan `http://localhost:5173`).

## Keterbatasan

- Model MarianMT ringan (~60-80MB per pasangan bahasa) — bagus untuk kalimat sehari-hari, tapi tidak sekuat model besar berbayar untuk teks panjang/teknis/ambigu.
- Placeholder numerik untuk melindungi URL/email/dll umumnya tahan, tapi pada teks yang aneh kadang ada pergeseran spasi kecil di sekitarnya — tes ulang untuk kasus pakai Anda.
- Auto-detect bahasa (`franc`) butuh teks yang cukup panjang untuk akurat; teks pendek/ambigu bisa gagal (`LANGUAGE_DETECTION_FAILED`) — kirim `from` eksplisit kalau memungkinkan.
- Unduhan model butuh internet sekali di awal; setelahnya 100% offline.
