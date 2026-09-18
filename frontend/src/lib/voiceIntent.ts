/**
 * Text → command parsing for the hands-free voice assistant (`stores/voice.ts`).
 *
 * Deliberately free of Vue, Pinia and asset imports: everything here is string
 * in / plain object out, so the parsing rules can be reasoned about (and tested)
 * without a browser. Note the keyword tables are keyed by `CategoryName` /
 * `LocationName`, which makes TypeScript reject the file the day a category or
 * kecamatan is added without teaching the assistant how to hear it.
 */
import type { CategoryName, LocationName } from '@/types'

export type VoiceIntent =
  | 'cari'
  | 'buka'
  | 'daftar'
  | 'halaman'
  | 'favorit'
  | 'ulangi'
  | 'berhenti'
  | 'bantuan'
  | 'beranda'
  | 'tidak_dikenal'

/** A static page reachable by name — everything except the UMKM catalog/detail/auth flows. */
export type PageName = 'panduan' | 'tentang' | 'akun' | 'privacy' | 'terms'

export interface ParsedCommand {
  intent: VoiceIntent
  category: CategoryName | null
  location: LocationName | null
  /** Which static page "buka halaman …" named, when `intent` is `'halaman'`. */
  page: PageName | null
  /** Free text left over once the intent/category/location words are removed. */
  keyword: string
  /** 1-based position for "buka nomor dua"; null when none was spoken. */
  index: number | null
  /** The normalized command, for the "saya dengar …" reply and for debugging. */
  raw: string
}

/**
 * Wake word. The alternatives are not sloppiness — `id-ID` recognition
 * transcribes an English brand name inconsistently, so "Oke NearBy" comes back
 * as "oke near by", "ok nearbi", "oke nerbi" and friends. Anything stricter
 * would leave the assistant unreachable for the very users it exists for.
 */
const WAKE_WORD =
  /\b(?:ok|oke|okey|okay|oce|hai|hei|hey|halo|hallo)\s+(?:near\s*b(?:y|i|ie|ye)|n(?:i|e)(?:a|e)r\s*b(?:i|y)|nearbi|nirbai|nirbi|nerbi|nearby)\b/

/** Lowercase, strip punctuation, collapse whitespace. Every matcher below assumes this shape. */
export function normalize(text: string): string {
  return (
    text
      .toLowerCase()
      .replace(/[^a-z0-9\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
      // "Balikpapan" is often heard as two words; fold it back so the kecamatan
      // patterns only have to spell it one way.
      .replace(/\bbalik\s+papan\b/g, 'balikpapan')
  )
}

export function hasWakeWord(text: string): boolean {
  return WAKE_WORD.test(normalize(text))
}

/**
 * Everything the speaker said *after* the wake word, or null when the wake word
 * isn't there. An empty string means the wake word was all they said — the
 * assistant answers and waits for the command instead of guessing.
 */
export function stripWakeWord(text: string): string | null {
  const t = normalize(text)
  const match = WAKE_WORD.exec(t)
  if (!match) return null
  return t.slice(match.index + match[0].length).trim()
}

const CATEGORY_KEYWORDS: Record<CategoryName, string[]> = {
  Kuliner: [
    'rumah makan',
    'tempat makan',
    'warung makan',
    'ikan bakar',
    'sea food',
    'makanan',
    'makan',
    'kuliner',
    'restoran',
    'restaurant',
    'warung',
    'warteg',
    'cafe',
    'kafe',
    'kopi',
    'coffee',
    'bakso',
    'soto',
    'sate',
    'nasi',
    'ayam',
    'seafood',
    'minuman',
    'minum',
    'jajanan',
    'kue',
    'roti',
    'martabak',
    'lapar',
  ],
  Penginapan: [
    'tempat menginap',
    'kamar sewa',
    'penginapan',
    'menginap',
    'hotel',
    'losmen',
    'homestay',
    'home stay',
    'wisma',
    'guest house',
    'guesthouse',
    'kosan',
    'kos',
  ],
  Fashion: [
    'toko baju',
    'fashion',
    'baju',
    'pakaian',
    'busana',
    'kaos',
    'kemeja',
    'batik',
    'sepatu',
    'sandal',
    'tas',
    'hijab',
    'jilbab',
    'konveksi',
    'distro',
  ],
  'Oleh-Oleh': [
    'oleh oleh',
    'buah tangan',
    'cinderamata',
    'cendera mata',
    'oleholeh',
    'souvenir',
    'suvenir',
    'kerajinan',
    'amplang',
    'kripik',
    'keripik',
  ],
  Jasa: [
    'potong rambut',
    'cuci mobil',
    'cuci motor',
    'jasa',
    'servis',
    'service',
    'bengkel',
    'laundry',
    'londri',
    'salon',
    'barbershop',
    'reparasi',
    'perbaikan',
    'percetakan',
    'fotokopi',
    'foto kopi',
  ],
}

/**
 * Kecamatan aliases. Every entry allows the bare compass word ("selatan")
 * *except* "Balikpapan Kota": "kota" on its own turns up in ordinary speech
 * ("makanan enak di kota ini"), so that one needs the full name to match.
 */
const LOCATION_KEYWORDS: Record<LocationName, string[]> = {
  'Balikpapan Kota': ['balikpapan kota', 'kota balikpapan'],
  'Balikpapan Utara': ['balikpapan utara', 'utara'],
  'Balikpapan Selatan': ['balikpapan selatan', 'selatan'],
  'Balikpapan Timur': ['balikpapan timur', 'timur'],
  'Balikpapan Barat': ['balikpapan barat', 'barat'],
  'Balikpapan Tengah': ['balikpapan tengah', 'tengah'],
}

/**
 * Named static pages. "Panduan" used to double as a synonym for "bantuan"
 * (spoken help), which meant "buka halaman panduan" read out the help menu
 * instead of opening the actual Panduan page — that collision is why it's a
 * dedicated table now instead of living inside `INTENT_KEYWORDS`.
 */
const PAGE_KEYWORDS: Record<PageName, string[]> = {
  panduan: ['halaman panduan', 'buka panduan', 'panduan mendaftar', 'cara mendaftar umkm', 'cara daftar umkm', 'panduan'],
  tentang: ['halaman tentang', 'tentang kami', 'tentang aplikasi', 'tentang nearby'],
  akun: ['halaman akun', 'akun saya', 'profil saya', 'pengaturan akun'],
  privacy: ['kebijakan privasi', 'halaman privasi', 'privasi'],
  terms: ['syarat dan ketentuan', 'ketentuan layanan', 'halaman ketentuan'],
}

const INTENT_KEYWORDS: { intent: VoiceIntent; words: string[] }[] = [
  { intent: 'berhenti', words: ['berhenti', 'hentikan', 'stop', 'diam', 'sudah cukup', 'batal', 'batalkan'] },
  { intent: 'ulangi', words: ['ulangi', 'ulang', 'sekali lagi', 'apa tadi', 'bacakan lagi'] },
  { intent: 'favorit', words: ['favorit', 'favoritku', 'kesukaan', 'yang disimpan', 'simpanan'] },
  {
    intent: 'bantuan',
    words: ['bantuan', 'bantu aku', 'bantu saya', 'perintah apa', 'bisa apa', 'apa saja', 'help'],
  },
  { intent: 'beranda', words: ['beranda', 'halaman utama', 'halaman depan', 'kembali ke awal', 'home'] },
  {
    intent: 'daftar',
    words: [
      'daftar umkm',
      'daftar semua umkm',
      'semua umkm',
      'direktori',
      'lihat semua umkm',
      'lihat semua',
      'tampilkan semua',
      'tampilkan semua umkm',
    ],
  },
  { intent: 'buka', words: ['buka', 'detail', 'rincian', 'nomor', 'pilih', 'lihat', 'ceritakan'] },
  {
    intent: 'cari',
    words: [
      'carikan',
      'cari',
      'temukan',
      'tunjukkan',
      'rekomendasikan',
      'rekomendasi',
      'mau',
      'pengen',
      'ingin',
      'butuh',
      'terdekat',
      'dekat',
      'sekitar',
    ],
  },
]

const ORDINALS: Record<string, number> = {
  satu: 1,
  pertama: 1,
  kesatu: 1,
  kedua: 2,
  dua: 2,
  ketiga: 3,
  tiga: 3,
  keempat: 4,
  empat: 4,
  kelima: 5,
  lima: 5,
  keenam: 6,
  enam: 6,
  tujuh: 7,
  delapan: 8,
  sembilan: 9,
  sepuluh: 10,
}

/** Filler that carries no meaning here but would otherwise pollute `keyword`. */
const STOPWORDS = new Set([
  'aku', 'saya', 'gue', 'kita', 'yang', 'di', 'ke', 'dari', 'pada', 'untuk',
  'buat', 'dong', 'ya', 'yah', 'nih', 'deh', 'sih', 'kah', 'tolong', 'coba',
  'dan', 'atau', 'itu', 'ini', 'ada', 'apa', 'saja', 'aja', 'juga', 'lagi',
  'sini', 'situ', 'daerah', 'wilayah', 'kecamatan', 'balikpapan', 'umkm',
  'tempat', 'nomor', 'yg', 'enak', 'bagus', 'murah', 'terbaik', 'adalah',
  'kembali', 'punya', 'toko',
])

function wordRegex(phrase: string): RegExp {
  return new RegExp(`\\b${phrase.replace(/\s+/g, '\\s+')}\\b`)
}

/** Longest phrase first, so "rumah makan" wins over "makan". */
function matchTable<T extends string>(
  text: string,
  table: Record<T, string[]>,
): { key: T; phrase: string } | null {
  const entries: { key: T; phrase: string }[] = []
  for (const key of Object.keys(table) as T[]) {
    for (const phrase of table[key]) entries.push({ key, phrase })
  }
  entries.sort((a, b) => b.phrase.length - a.phrase.length)
  return entries.find((e) => wordRegex(e.phrase).test(text)) ?? null
}

function detectIndex(text: string): number | null {
  const digits = /\bnomor\s+(\d{1,2})\b/.exec(text) ?? /\b(\d{1,2})\b/.exec(text)
  if (digits) {
    const n = Number(digits[1])
    if (n >= 1 && n <= 20) return n
  }
  for (const [word, value] of Object.entries(ORDINALS)) {
    if (wordRegex(word).test(text)) return value
  }
  return null
}

/**
 * Parse one spoken command (the wake word already removed).
 *
 * Intent detection is ordered, not scored: control words ("berhenti", "ulangi")
 * must win over "cari" even when both appear, because getting those wrong leaves
 * someone who cannot see the screen stuck listening to something they just asked
 * to stop.
 */
export function parseCommand(input: string): ParsedCommand {
  const raw = normalize(input)

  const categoryHit = matchTable(raw, CATEGORY_KEYWORDS)
  const locationHit = matchTable(raw, LOCATION_KEYWORDS)
  const pageHit = matchTable(raw, PAGE_KEYWORDS)
  const index = detectIndex(raw)

  let intent: VoiceIntent = 'tidak_dikenal'
  for (const { intent: candidate, words } of INTENT_KEYWORDS) {
    if (words.some((w) => wordRegex(w).test(raw))) {
      intent = candidate
      break
    }
  }

  // "buka" only means "open result N" when a position was actually spoken;
  // "buka jam berapa" is a question about opening hours, not a command.
  // Failing that, a named page ("buka halaman panduan") or a category/
  // kecamatan ("buka kuliner") tell us what to open instead; with none of
  // those either, it isn't a recognizable command.
  if (intent === 'buka' && index === null) {
    intent = categoryHit ? 'cari' : locationHit ? 'cari' : pageHit ? 'halaman' : 'tidak_dikenal'
  }

  // The 'daftar' catch-all keywords ("lihat semua", "tampilkan semua", …) are
  // deliberately generic, so they win the ordered match above even when a
  // category/kecamatan was also named ("tampilkan semua kuliner di selatan")
  // or when the phrase was really about a named page ("cara daftar umkm"
  // overlaps "daftar umkm"). Both are more specific than a bare directory
  // listing, so they take priority over it.
  if (intent === 'daftar' && (categoryHit || locationHit)) intent = 'cari'
  if (intent === 'daftar' && pageHit) intent = 'halaman'

  // Naming a category or a kecamatan is a search on its own — someone saying
  // just "makanan di Balikpapan Selatan" means the obvious thing. Same for a
  // named page: "halaman panduan" alone is still a request to open it.
  if (intent === 'tidak_dikenal' && (categoryHit || locationHit)) intent = 'cari'
  if (intent === 'tidak_dikenal' && pageHit) intent = 'halaman'

  let keyword = raw
  for (const phrase of [categoryHit?.phrase, locationHit?.phrase, pageHit?.phrase]) {
    if (phrase) keyword = keyword.replace(wordRegex(phrase), ' ')
  }
  // Every command word goes, not just the one that decided the intent:
  // "carikan aku makanan terdekat" carries two of them ("carikan", "terdekat")
  // and either one left behind would be searched for as if it were a shop name.
  for (const { words } of INTENT_KEYWORDS) {
    for (const word of words) keyword = keyword.replace(new RegExp(wordRegex(word), 'g'), ' ')
  }
  keyword = keyword
    .split(/\s+/)
    .filter((w) => w && !STOPWORDS.has(w) && !(w in ORDINALS) && !/^\d+$/.test(w))
    .join(' ')
    .trim()

  return {
    intent,
    category: categoryHit?.key ?? null,
    location: locationHit?.key ?? null,
    page: pageHit?.key ?? null,
    keyword,
    index,
    raw,
  }
}
