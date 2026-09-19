"""
Pemahaman perintah suara NearBy: teks transkrip -> intent + entitas + kalimat jawaban.

Rule-based, bukan model statistik: belum ada korpus ucapan pengguna NearBy untuk
melatih model, dan model NER umum (mis. spaCy) tidak mengenal nama kecamatan
Balikpapan maupun kategori UMKM kita. Aturan di sini bisa dibaca, dites, dan
diperbaiki satu per satu - lebih cocok untuk fitur aksesibilitas yang harus
bisa diandalkan.

Alurnya:
  1. normalisasi teks (huruf kecil, tanpa tanda baca, "balik papan" -> "balikpapan")
  2. koreksi kata yang salah dengar ("selatn" -> "selatan") dengan fuzzy matching
  3. deteksi kata bangun "Oke NearBy" bila diminta
  4. ekstraksi entitas: kategori, lokasi (kecamatan), halaman, nomor urut
  5. penentuan intent secara berurutan (perintah kontrol menang atas "cari")
  6. penyusunan kalimat jawaban yang langsung dibacakan frontend

Tabel kata kunci sengaja disamakan dengan `frontend/src/lib/voiceIntent.ts`,
yang tetap dipakai frontend sebagai cadangan saat layanan ini tidak bisa dihubungi.
"""

from __future__ import annotations

import difflib
import re
from dataclasses import dataclass, field

# ---------------------------------------------------------------------------
# Kosakata
# ---------------------------------------------------------------------------

# Hasil transkrip id-ID untuk nama merek berbahasa Inggris sangat tidak konsisten
# ("oke near by", "ok nearbi", "oke nerbi", ...), jadi semua variasi diterima.
WAKE_WORD = re.compile(
    r"\b(?:ok|oke|okey|okay|oce|hai|hei|hey|halo|hallo)\s+"
    r"(?:near\s*b(?:y|i|ie|ye)|n(?:i|e)(?:a|e)r\s*b(?:i|y)|nearbi|nirbai|nirbi|nerbi|nearby)\b"
)

CATEGORY_KEYWORDS: dict[str, list[str]] = {
    "Kuliner": [
        "rumah makan", "tempat makan", "warung makan", "ikan bakar", "sea food",
        "makanan", "makan", "kuliner", "restoran", "restaurant", "warung", "warteg",
        "cafe", "kafe", "kopi", "coffee", "bakso", "soto", "sate", "nasi", "ayam",
        "seafood", "minuman", "minum", "jajanan", "kue", "roti", "martabak", "lapar",
    ],
    "Penginapan": [
        "tempat menginap", "kamar sewa", "penginapan", "menginap", "hotel", "losmen",
        "homestay", "home stay", "wisma", "guest house", "guesthouse", "kosan", "kos",
    ],
    "Fashion": [
        "toko baju", "fashion", "baju", "pakaian", "busana", "kaos", "kemeja", "batik",
        "sepatu", "sandal", "tas", "hijab", "jilbab", "konveksi", "distro",
    ],
    "Oleh-Oleh": [
        "oleh oleh", "buah tangan", "cinderamata", "cendera mata", "oleholeh",
        "souvenir", "suvenir", "kerajinan", "amplang", "kripik", "keripik",
    ],
    "Jasa": [
        "potong rambut", "cuci mobil", "cuci motor", "jasa", "servis", "service",
        "bengkel", "laundry", "londri", "salon", "barbershop", "reparasi", "perbaikan",
        "percetakan", "fotokopi", "foto kopi",
    ],
}

# "Balikpapan Kota" wajib disebut lengkap: kata "kota" saja terlalu sering muncul
# dalam ucapan biasa ("makanan enak di kota ini").
LOCATION_KEYWORDS: dict[str, list[str]] = {
    "Balikpapan Kota": ["balikpapan kota", "kota balikpapan"],
    "Balikpapan Utara": ["balikpapan utara", "utara"],
    "Balikpapan Selatan": ["balikpapan selatan", "selatan"],
    "Balikpapan Timur": ["balikpapan timur", "timur"],
    "Balikpapan Barat": ["balikpapan barat", "barat"],
    "Balikpapan Tengah": ["balikpapan tengah", "tengah"],
}

PAGE_KEYWORDS: dict[str, list[str]] = {
    "panduan": ["halaman panduan", "buka panduan", "panduan mendaftar", "cara mendaftar umkm", "cara daftar umkm", "panduan"],
    "tentang": ["halaman tentang", "tentang kami", "tentang aplikasi", "tentang nearby"],
    "akun": ["halaman akun", "akun saya", "profil saya", "pengaturan akun"],
    "privacy": ["kebijakan privasi", "halaman privasi", "privasi"],
    "terms": ["syarat dan ketentuan", "ketentuan layanan", "halaman ketentuan"],
}

PAGE_LABELS = {
    "panduan": "Panduan",
    "tentang": "Tentang Kami",
    "akun": "Akun",
    "privacy": "Kebijakan Privasi",
    "terms": "Syarat dan Ketentuan",
}

# Urutan penting: perintah kontrol ("berhenti", "ulangi") harus menang atas
# "cari" walau keduanya muncul - salah tangkap di sini membuat pengguna yang
# tidak melihat layar terjebak mendengarkan hal yang baru saja ia minta hentikan.
INTENT_KEYWORDS: list[tuple[str, list[str]]] = [
    ("berhenti", ["berhenti", "hentikan", "stop", "diam", "sudah cukup", "batal", "batalkan"]),
    ("ulangi", ["ulangi", "ulang", "sekali lagi", "apa tadi", "bacakan lagi"]),
    ("favorit", ["favorit", "favoritku", "kesukaan", "yang disimpan", "simpanan"]),
    ("bantuan", ["bantuan", "bantu aku", "bantu saya", "perintah apa", "bisa apa", "apa saja", "help"]),
    ("beranda", ["beranda", "halaman utama", "halaman depan", "kembali ke awal", "home"]),
    ("daftar", [
        "daftar umkm", "daftar semua umkm", "semua umkm", "direktori", "lihat semua umkm",
        "lihat semua", "tampilkan semua", "tampilkan semua umkm",
    ]),
    ("buka", ["buka", "detail", "rincian", "nomor", "pilih", "lihat", "ceritakan"]),
    ("cari", [
        "carikan", "cari", "temukan", "tunjukkan", "rekomendasikan", "rekomendasi", "mau",
        "pengen", "ingin", "butuh", "terdekat", "dekat", "sekitar",
    ]),
]

ORDINALS = {
    "satu": 1, "pertama": 1, "kesatu": 1, "kedua": 2, "dua": 2, "ketiga": 3, "tiga": 3,
    "keempat": 4, "empat": 4, "kelima": 5, "lima": 5, "keenam": 6, "enam": 6,
    "tujuh": 7, "delapan": 8, "sembilan": 9, "sepuluh": 10,
}

STOPWORDS = {
    "aku", "saya", "gue", "kita", "yang", "di", "ke", "dari", "pada", "untuk", "buat",
    "dong", "ya", "yah", "nih", "deh", "sih", "kah", "tolong", "coba", "dan", "atau",
    "itu", "ini", "ada", "apa", "saja", "aja", "juga", "lagi", "sini", "situ", "daerah",
    "wilayah", "kecamatan", "balikpapan", "umkm", "tempat", "nomor", "yg", "enak",
    "bagus", "murah", "terbaik", "adalah", "kembali", "punya", "toko",
}

# Kata kategori yang berupa kata kerja/keadaan dibacakan sebagai kata benda:
# "aku lapar" -> "Baik, mencari makanan ...", bukan "mencari lapar".
SPOKEN_NOUN = {
    "makan": "makanan", "lapar": "makanan", "tempat makan": "tempat makan",
    "minum": "minuman", "menginap": "penginapan", "tempat menginap": "penginapan",
    "oleholeh": "oleh-oleh", "oleh oleh": "oleh-oleh", "londri": "laundry",
}

# Slug kategori untuk intent terperinci, mis. "cari_makanan".
CATEGORY_SLUG = {
    "Kuliner": "makanan",
    "Penginapan": "penginapan",
    "Fashion": "fashion",
    "Oleh-Oleh": "oleh_oleh",
    "Jasa": "jasa",
}

HELP_TEXT = (
    "Ucapkan Oke NearBy lebih dulu, lalu perintahnya. "
    "Contohnya: carikan aku makanan terdekat di Balikpapan Selatan. "
    "Anda juga bisa bilang buka daftar UMKM untuk melihat semuanya, "
    "buka halaman panduan untuk cara mendaftar, "
    "buka nomor dua untuk mendengar detail, "
    "favorit saya, ulangi, kembali ke beranda, atau berhenti."
)

# ---------------------------------------------------------------------------
# Normalisasi & koreksi salah dengar
# ---------------------------------------------------------------------------


def normalize(text: str) -> str:
    t = text.lower()
    t = re.sub(r"[^a-z0-9\s]", " ", t)
    t = re.sub(r"\s+", " ", t).strip()
    # "Balikpapan" sering tertangkap dua kata; satukan supaya pola cukup satu ejaan.
    return re.sub(r"\bbalik\s+papan\b", "balikpapan", t)


def _single_words(*tables: dict[str, list[str]]) -> set[str]:
    words: set[str] = set()
    for table in tables:
        for phrases in table.values():
            words.update(p for p in phrases if " " not in p)
    return words


# Hanya kata >= 5 huruf yang dikoreksi: kata pendek terlalu mudah "dikoreksi"
# jadi kata lain yang sama sekali berbeda artinya.
_VOCAB = sorted(
    w
    for w in _single_words(CATEGORY_KEYWORDS, LOCATION_KEYWORDS, PAGE_KEYWORDS, dict(INTENT_KEYWORDS))
    | {"balikpapan"}
    if len(w) >= 5
)
_FUZZY_CUTOFF = 0.85


def correct_typos(text: str) -> str:
    """
    "carikan makanaan di balikpapan selatn" -> "carikan makanan di balikpapan selatan".

    Kata yang sudah dikenal, stopword, dan angka dibiarkan apa adanya.
    """
    known = set(_VOCAB) | STOPWORDS | set(ORDINALS)
    out = []
    for word in text.split():
        if len(word) >= 5 and word not in known and not word.isdigit():
            match = difflib.get_close_matches(word, _VOCAB, n=1, cutoff=_FUZZY_CUTOFF)
            if match:
                word = match[0]
        out.append(word)
    return " ".join(out)


# ---------------------------------------------------------------------------
# Ekstraksi entitas
# ---------------------------------------------------------------------------


def _word_regex(phrase: str) -> re.Pattern[str]:
    return re.compile(r"\b" + r"\s+".join(map(re.escape, phrase.split())) + r"\b")


def _match_table(text: str, table: dict[str, list[str]]) -> tuple[str, str] | None:
    """(kunci, frasa) yang cocok; frasa terpanjang menang, jadi "rumah makan" mengalahkan "makan"."""
    entries = sorted(
        ((key, phrase) for key, phrases in table.items() for phrase in phrases),
        key=lambda e: len(e[1]),
        reverse=True,
    )
    for key, phrase in entries:
        if _word_regex(phrase).search(text):
            return key, phrase
    return None


def _detect_index(text: str) -> int | None:
    m = re.search(r"\bnomor\s+(\d{1,2})\b", text) or re.search(r"\b(\d{1,2})\b", text)
    if m and 1 <= int(m.group(1)) <= 20:
        return int(m.group(1))
    for word, value in ORDINALS.items():
        if _word_regex(word).search(text):
            return value
    return None


# ---------------------------------------------------------------------------
# Hasil
# ---------------------------------------------------------------------------


@dataclass
class Interpretation:
    #: Intent terperinci untuk ditampilkan/dicatat, mis. "cari_makanan".
    intent: str
    #: Intent dasar yang dieksekusi frontend: cari, buka, daftar, halaman, favorit,
    #: ulangi, berhenti, bantuan, beranda, tidak_dikenal, siaga_perintah, abaikan.
    action: str
    #: Lokasi dalam huruf kecil, mis. "balikpapan selatan"; None bila tidak disebut.
    location: str | None
    #: Kalimat yang langsung dibacakan frontend.
    message: str
    #: Apakah kata bangun "Oke NearBy" terdengar.
    wake: bool
    entities: dict = field(default_factory=dict)
    #: Teks setelah normalisasi & koreksi, untuk jawaban "saya belum mengerti ..." dan debugging.
    text: str = ""


def _ignore(text: str) -> Interpretation:
    return Interpretation("abaikan", "abaikan", None, "", False, _entities(), text)


def _entities(category=None, location=None, page=None, keyword="", index=None) -> dict:
    return {"category": category, "location": location, "page": page, "keyword": keyword, "index": index}


def interpret(text: str, require_wake: bool = False) -> Interpretation:
    """
    Tafsirkan satu ucapan.

    `require_wake=True` dipakai saat asisten sedang siaga: ucapan tanpa "Oke
    NearBy" adalah obrolan di sekitar pengguna dan dijawab `abaikan`. Saat
    asisten sudah dibangunkan (menunggu perintah), frontend mengirim False.
    """
    normalized = normalize(text)
    wake = False

    if require_wake:
        m = WAKE_WORD.search(normalized)
        if not m:
            return _ignore(normalized)
        wake = True
        normalized = normalized[m.end():].strip()
        if not normalized:
            # Hanya kata bangun: jawab dan beri kesempatan menyebut perintahnya.
            return Interpretation("siaga_perintah", "siaga_perintah", None, "Ya, silakan.", True, _entities(), "")

    return _parse_command(correct_typos(normalized), wake)


def _parse_command(raw: str, wake: bool) -> Interpretation:
    category_hit = _match_table(raw, CATEGORY_KEYWORDS)
    location_hit = _match_table(raw, LOCATION_KEYWORDS)
    page_hit = _match_table(raw, PAGE_KEYWORDS)
    index = _detect_index(raw)

    action = "tidak_dikenal"
    for candidate, words in INTENT_KEYWORDS:
        if any(_word_regex(w).search(raw) for w in words):
            action = candidate
            break

    # "buka" hanya berarti "buka hasil nomor N" bila nomornya disebut; "buka jam
    # berapa" adalah pertanyaan. Tanpa nomor, kategori/lokasi/halaman yang menentukan.
    if action == "buka" and index is None:
        action = "cari" if (category_hit or location_hit) else "halaman" if page_hit else "tidak_dikenal"

    # Kata kunci "daftar" sengaja umum ("tampilkan semua"), jadi kategori/lokasi
    # atau nama halaman yang disebut bersamanya lebih spesifik.
    if action == "daftar" and (category_hit or location_hit):
        action = "cari"
    if action == "daftar" and page_hit:
        action = "halaman"

    # Menyebut kategori/lokasi saja sudah berarti mencari: "makanan di balikpapan selatan".
    if action == "tidak_dikenal" and (category_hit or location_hit):
        action = "cari"
    if action == "tidak_dikenal" and page_hit:
        action = "halaman"

    keyword = raw
    for hit in (category_hit, location_hit, page_hit):
        if hit:
            keyword = _word_regex(hit[1]).sub(" ", keyword, count=1)
    # Semua kata perintah dibuang, bukan hanya yang menentukan intent:
    # "carikan aku makanan terdekat" punya dua ("carikan", "terdekat").
    for _, words in INTENT_KEYWORDS:
        for word in words:
            keyword = _word_regex(word).sub(" ", keyword)
    keyword = " ".join(
        w for w in keyword.split() if w not in STOPWORDS and w not in ORDINALS and not w.isdigit()
    )

    category = category_hit[0] if category_hit else None
    location = location_hit[0] if location_hit else None
    page = page_hit[0] if page_hit else None

    intent = action
    if action == "cari":
        intent = f"cari_{CATEGORY_SLUG[category]}" if category else "cari_umkm"
    elif action == "halaman" and page:
        intent = f"buka_halaman_{page}"
    elif action == "buka":
        intent = "buka_detail"

    return Interpretation(
        intent=intent,
        action=action,
        location=location.lower() if location else None,
        message=_message(action, raw, category_hit, location, page, keyword, index),
        wake=wake,
        entities=_entities(category, location, page, keyword, index),
        text=raw,
    )


def _message(action, raw, category_hit, location, page, keyword, index) -> str:
    if action == "cari":
        if category_hit:
            what = SPOKEN_NOUN.get(category_hit[1], category_hit[1])
            if keyword and keyword != what:
                what = f"{what} {keyword}"
        else:
            what = keyword or "UMKM"
        where = location.lower() if location else "sekitar Balikpapan"
        return f"Baik, mencari {what} di {where}."
    if action == "buka":
        return f"Baik, membuka nomor {index}."
    if action == "daftar":
        return "Baik, membuka daftar semua UMKM."
    if action == "halaman":
        if not page:
            return "Maaf, saya belum mengerti halaman yang dimaksud. Ucapkan bantuan untuk mendengar daftar perintah."
        return f"Membuka halaman {PAGE_LABELS[page]}."
    if action == "favorit":
        return "Baik, membuka daftar favorit Anda."
    if action == "beranda":
        return "Kembali ke halaman utama."
    if action == "berhenti":
        return "Baik, saya berhenti."
    if action == "bantuan":
        return HELP_TEXT
    if action == "ulangi":
        # Frontend mengulang jawaban terakhirnya sendiri.
        return ""
    return f'Maaf, saya belum mengerti "{raw}". Ucapkan bantuan untuk mendengar daftar perintah.'
