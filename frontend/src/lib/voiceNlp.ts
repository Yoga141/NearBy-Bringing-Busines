/**
 * Client for the Python NLP service (`nlp-service/`, `POST /api/voice-nlp`).
 *
 * Understanding what was said lives on the server now; this file only ships
 * the transcript there and turns the JSON back into something `stores/voice.ts`
 * can act on. The response is checked field by field rather than trusted: a
 * category or kecamatan the directory doesn't know would otherwise filter the
 * list down to nothing and the assistant would report "tidak menemukan".
 *
 * If the service can't be reached the local rule parser (`voiceIntent.ts`)
 * answers instead. For someone who can't see the screen a silent assistant is
 * the worst failure there is, so a slower or offline NLP must not become one.
 */
import type { CategoryName, LocationName } from '@/types'
import { parseCommand, stripWakeWord, type PageName, type VoiceIntent } from './voiceIntent'

/** `VoiceIntent` plus the two answers that only exist around the wake word. */
export type VoiceAction = VoiceIntent | 'siaga_perintah' | 'abaikan'

export interface VoiceCommand {
  /** What the store executes. */
  action: VoiceAction
  /** Fine-grained intent from the server, e.g. "cari_makanan" - informational. */
  intent: string
  category: CategoryName | null
  location: LocationName | null
  page: PageName | null
  keyword: string
  index: number | null
  /** Sentence to read out right away; empty when the store words its own reply. */
  message: string
  /** The command as understood, for "saya belum mengerti …". */
  raw: string
  source: 'python' | 'lokal'
}

/**
 * Only set when the Python service is actually deployed (see .env.development
 * for local use). The cPanel host runs PHP only, so a production build without
 * it goes straight to the in-browser rules instead of a request that 404s.
 */
const ENDPOINT: string = import.meta.env.VITE_VOICE_NLP_URL || ''
/** Past this the user has been waiting in silence long enough - answer locally. */
const TIMEOUT_MS = 4_000

const ACTIONS: readonly VoiceAction[] = [
  'cari', 'buka', 'daftar', 'halaman', 'favorit', 'ulangi', 'berhenti', 'bantuan',
  'beranda', 'tidak_dikenal', 'siaga_perintah', 'abaikan',
]
const CATEGORIES: readonly CategoryName[] = ['Kuliner', 'Penginapan', 'Fashion', 'Oleh-Oleh', 'Jasa']
const LOCATIONS: readonly LocationName[] = [
  'Balikpapan Kota', 'Balikpapan Utara', 'Balikpapan Selatan',
  'Balikpapan Timur', 'Balikpapan Barat', 'Balikpapan Tengah',
]
const PAGES: readonly PageName[] = ['panduan', 'tentang', 'akun', 'privacy', 'terms']

function oneOf<T extends string>(list: readonly T[], value: unknown): T | null {
  return list.includes(value as T) ? (value as T) : null
}

interface ServerResponse {
  intent?: unknown
  action?: unknown
  message?: unknown
  text?: unknown
  entities?: {
    category?: unknown
    location?: unknown
    page?: unknown
    keyword?: unknown
    index?: unknown
  }
}

function fromServer(body: ServerResponse): VoiceCommand | null {
  const action = oneOf(ACTIONS, body.action)
  if (!action) return null
  const e = body.entities ?? {}
  const index = typeof e.index === 'number' && e.index >= 1 && e.index <= 20 ? e.index : null
  return {
    action,
    intent: typeof body.intent === 'string' ? body.intent : action,
    category: oneOf(CATEGORIES, e.category),
    location: oneOf(LOCATIONS, e.location),
    page: oneOf(PAGES, e.page),
    keyword: typeof e.keyword === 'string' ? e.keyword : '',
    index,
    message: typeof body.message === 'string' ? body.message : '',
    raw: typeof body.text === 'string' ? body.text : '',
    source: 'python',
  }
}

/** Same contract as the server, answered by the in-browser rules. */
function interpretLocally(text: string, requireWake: boolean): VoiceCommand {
  const base = {
    category: null, location: null, page: null, keyword: '', index: null, message: '', raw: '',
    source: 'lokal' as const,
  }
  let command = text
  if (requireWake) {
    const afterWake = stripWakeWord(text)
    if (afterWake === null) return { ...base, action: 'abaikan', intent: 'abaikan' }
    if (!afterWake) return { ...base, action: 'siaga_perintah', intent: 'siaga_perintah', message: 'Ya, silakan.' }
    command = afterWake
  }
  const parsed = parseCommand(command)
  return { ...parsed, action: parsed.intent, intent: parsed.intent, message: '', source: 'lokal' }
}

/**
 * Interpret one utterance. `requireWake` is true while the assistant is idle
 * ("siaga"): anything without "Oke NearBy" comes back as `abaikan`.
 */
export async function interpret(text: string, requireWake: boolean): Promise<VoiceCommand> {
  if (!ENDPOINT) return interpretLocally(text, requireWake)

  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), TIMEOUT_MS)
  try {
    const res = await fetch(ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ text, require_wake: requireWake }),
      signal: controller.signal,
    })
    if (res.ok) {
      const command = fromServer((await res.json()) as ServerResponse)
      if (command) return command
    }
  } catch {
    // Offline, timed out, or service not running - fall through.
  } finally {
    clearTimeout(timer)
  }
  return interpretLocally(text, requireWake)
}
