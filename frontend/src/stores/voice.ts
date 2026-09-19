import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import router from '@/router'
import { useUmkmStore, type EnrichedUmkm } from './umkm'
import { useAuthStore } from './auth'
import { useA11yStore } from './a11y'
import type { PageName } from '@/lib/voiceIntent'
import { interpret, type VoiceCommand } from '@/lib/voiceNlp'

/**
 * Hands-free voice assistant for blind and low-vision visitors.
 *
 * The whole feature is ear-only: nothing is drawn on screen, and every answer
 * leaves through `speechSynthesis`. The flow is
 *
 *   siaga → (wake word "Oke NearBy") → mendengar → memproses → bicara → siaga
 *
 * Two things shape the implementation more than anything else:
 *
 *  1. The microphone must be closed while we talk. Recognition would otherwise
 *     transcribe our own answer and trigger itself in a loop, so every reply
 *     stops recognition first and restarts it when the last word is out.
 *  2. Chrome ends a recognition session on its own every few seconds (and after
 *     every result). "Always listening" is therefore a restart loop, not one
 *     long session - see `scheduleRestart`. A keep-alive tick backs that loop
 *     up, so a session that dies without an `end` event (tab hidden, engine
 *     hiccup, a command that threw) still comes back. The only ways out are
 *     the user switching the assistant off or leaving the page.
 *
 * What an utterance means is decided by the Python NLP service
 * (`nlp-service/`, via `lib/voiceNlp.ts`), which also supplies the first
 * sentence of the answer; this store only executes the result.
 *
 * Searching reuses the directory filters in the umkm store instead of querying
 * separately, so a sighted helper looking over the user's shoulder sees exactly
 * the list being read out.
 */

/** Where the assistant is in the wake-word → answer cycle. */
export type VoicePhase = 'mati' | 'siaga' | 'mendengar' | 'memproses' | 'bicara'

/** How long we keep listening for the command after a bare "Oke NearBy". */
const COMMAND_WINDOW_MS = 12_000
/** Results read out loud per search. More than this is a wall of speech. */
const SPOKEN_RESULTS = 5
/** How often the keep-alive checks that the microphone is really open. */
const KEEPALIVE_MS = 3_000
/** A 'bicara'/'memproses' phase older than this with nothing playing is stuck. */
const STUCK_MS = 45_000
/** Longest we wait for the first `start` event (the permission prompt) before greeting anyway. */
const MIC_READY_TIMEOUT_MS = 15_000

/** Spoken the first time the microphone comes on after a tap or key press. */
export const ACTIVATION_GREETING =
  'Mode aksesibilitas suara aktif. Silakan ucapkan Oke Near By untuk mulai mencari.'

const HELP_TEXT =
  'Ucapkan Oke NearBy lebih dulu, lalu perintahnya. ' +
  'Contohnya: carikan aku makanan terdekat di Balikpapan Selatan. ' +
  'Anda juga bisa bilang buka daftar UMKM untuk melihat semuanya, ' +
  'buka halaman panduan untuk cara mendaftar, ' +
  'buka nomor dua untuk mendengar detail, ' +
  'favorit saya, ulangi, kembali ke beranda, atau berhenti.'

/** Spoken name for each named static page - matches the router's own route names. */
const PAGE_LABELS: Record<PageName, string> = {
  panduan: 'Panduan',
  tentang: 'Tentang Kami',
  akun: 'Akun',
  privacy: 'Kebijakan Privasi',
  terms: 'Syarat dan Ketentuan',
}

/** "4.75" → "4,8", which an Indonesian voice reads as "empat koma delapan". */
function spokenRating(rating: number): string {
  return rating.toFixed(1).replace('.', ',')
}

/** Digits one by one - "0812…" is otherwise read as one huge number. */
function spokenDigits(text: string): string {
  return text.replace(/\d/g, (d) => `${d} `).trim()
}

function describeOne(u: EnrichedUmkm, position: number): string {
  const parts = [`Nomor ${position}. ${u.name}.`]
  if (u.tag) parts.push(`${u.tag}.`)
  parts.push(`Rating ${spokenRating(u.rating)} dari ${u.reviews} ulasan.`)
  if (u.priceLabel) parts.push(`Kisaran harga ${u.priceLabel}.`)
  if (u.status !== 'Aktif') parts.push(`Sedang ${u.status.toLowerCase()}.`)
  return parts.join(' ')
}

function describeDetail(u: EnrichedUmkm): string {
  const parts = [`${u.name}. Kategori ${u.cat}, di ${u.loc}.`]
  if (u.tag) parts.push(`${u.tag}.`)
  parts.push(`Rating ${spokenRating(u.rating)} dari 5, berdasarkan ${u.reviews} ulasan.`)
  if (u.address) parts.push(`Alamat: ${u.address}.`)
  if (u.hours) parts.push(`Jam buka: ${u.hours}.`)
  if (u.phone) parts.push(`Telepon: ${spokenDigits(u.phone)}.`)
  parts.push(`Status saat ini ${u.status.toLowerCase()}.`)

  const available = u.items.filter((it) => it.avail !== false).slice(0, 3)
  if (available.length) {
    const menu = available.map((it) => (it.price ? `${it.name}, ${it.price}` : it.name)).join('. ')
    parts.push(`Beberapa ${u.listLabel || 'menu'}: ${menu}.`)
  }
  return parts.join(' ')
}

export const useVoiceStore = defineStore('voice', () => {
  const ttsSupported = typeof window !== 'undefined' && 'speechSynthesis' in window
  const sttSupported =
    typeof window !== 'undefined' && !!(window.SpeechRecognition ?? window.webkitSpeechRecognition)
  const supported = ttsSupported && sttSupported

  const enabled = ref(false)
  const phase = ref<VoicePhase>('mati')
  const error = ref('')
  /** Last thing heard and last thing said - mirrored into an aria-live region. */
  const heard = ref('')
  const spoken = ref('')
  /** Results of the last search, so "buka nomor dua" has something to refer to. */
  const results = ref<EnrichedUmkm[]>([])

  const listening = computed(() => phase.value === 'siaga' || phase.value === 'mendengar')

  let recognition: SpeechRecognition | null = null
  let recognitionRunning = false
  /** Should recognition be running whenever we are not talking? */
  let wantRecognition = false
  let restartTimer: ReturnType<typeof setTimeout> | undefined
  let commandTimer: ReturnType<typeof setTimeout> | undefined
  let keepAliveTimer: ReturnType<typeof setInterval> | undefined
  /** Consecutive sessions that ended in a real error - drives the restart backoff. */
  let failures = 0
  /** Has any session reached `start` yet, i.e. is the microphone permission granted? */
  let micConfirmed = false
  /** When the current phase began, for the stuck-phase guard in the keep-alive. */
  let phaseSince = Date.now()
  /** Resolves `enable()`'s wait for the microphone to actually open. */
  let micWaiter: ((ok: boolean) => void) | null = null

  watch(phase, () => {
    phaseSince = Date.now()
  })

  // ---- Keluaran suara ----

  function cancelSpeech() {
    if (ttsSupported) window.speechSynthesis.cancel()
  }

  /**
   * Say something, with the microphone closed for the duration.
   *
   * Resolves when the last chunk ends. The watchdog is not paranoia: some
   * engines never fire `onend` if the utterance is cancelled mid-flight, and a
   * promise that never settles would strand the assistant in 'bicara' with the
   * microphone off - i.e. permanently deaf.
   */
  function speak(text: string): Promise<void> {
    spoken.value = text
    if (!ttsSupported || !text) return Promise.resolve()

    const a11y = useA11yStore()
    phase.value = 'bicara'
    stopRecognition()
    cancelSpeech()

    return new Promise<void>((resolve) => {
      let settled = false
      const finish = () => {
        if (settled) return
        settled = true
        clearTimeout(watchdog)
        resolve()
      }

      // Long answers get truncated by some engines, so speak in sentences.
      const chunks = text.match(/[^.!?]+[.!?]*\s*/g) ?? [text]
      const utterances = chunks.map((chunk) => {
        const u = new SpeechSynthesisUtterance(chunk.trim())
        u.lang = 'id-ID'
        u.rate = a11y.speechRate
        return u
      })

      const last = utterances[utterances.length - 1]
      last.onend = finish
      last.onerror = finish

      // ~13 characters per second at 1x, plus headroom.
      const estimate = (text.length / 13 / Math.max(a11y.speechRate, 0.5)) * 1000 + 4000
      const watchdog = setTimeout(finish, estimate)

      // Chrome sometimes leaves the engine paused after a cancel(), and every
      // later utterance then queues silently behind it.
      window.speechSynthesis.resume()
      for (const u of utterances) window.speechSynthesis.speak(u)
    })
  }

  /**
   * Must run inside a tap/key handler: iOS Safari and Chrome only let a page
   * talk once it has spoken during a user gesture. An empty utterance is
   * enough to unlock every later `speak()` for the rest of the session.
   */
  function unlockSpeech() {
    if (!ttsSupported) return
    try {
      const u = new SpeechSynthesisUtterance('')
      u.volume = 0
      window.speechSynthesis.speak(u)
    } catch {
      // Nothing to unlock on engines that don't need it.
    }
  }

  /** Speak, then go back to waiting for the wake word. */
  async function reply(text: string) {
    await speak(text)
    if (!enabled.value) return
    phase.value = 'siaga'
    startRecognition()
  }

  // ---- Masukan suara ----

  function createRecognition(): SpeechRecognition | null {
    const Ctor = window.SpeechRecognition ?? window.webkitSpeechRecognition
    if (!Ctor) return null

    const rec = new Ctor()
    rec.lang = 'id-ID'
    // `continuous` still ends sessions on its own in Chrome; the restart loop
    // below is what actually keeps the assistant listening.
    rec.continuous = true
    rec.interimResults = false
    rec.maxAlternatives = 1

    rec.onresult = (event) => {
      let transcript = ''
      for (let i = event.resultIndex; i < event.results.length; i++) {
        if (event.results[i].isFinal) transcript += event.results[i][0].transcript
      }
      transcript = transcript.trim()
      if (transcript) void handleTranscript(transcript)
    }

    rec.onstart = () => {
      if (rec !== recognition) return
      recognitionRunning = true
      micConfirmed = true
      failures = 0
      if (error.value) error.value = ''
      micWaiter?.(true)
    }

    rec.onerror = (event) => {
      if (rec !== recognition) return
      // 'no-speech' and 'aborted' are the normal rhythm of a restart loop, not
      // failures worth telling the user about.
      if (event.error === 'no-speech' || event.error === 'aborted') return
      if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
        // The restart loop cannot fix a refused permission. Stop, say so, and
        // let the next tap or key press (VoiceAssistant.vue) try again.
        micWaiter?.(false)
        const message =
          'Izin mikrofon ditolak. Aktifkan izin mikrofon di peramban, lalu sentuh layar untuk mencoba lagi.'
        disable()
        error.value = message
        announce(message)
        return
      }
      failures++
      if (event.error === 'network') {
        error.value = 'Pengenalan suara butuh koneksi internet. Saya akan terus mencoba.'
      } else if (event.error === 'audio-capture') {
        error.value = 'Mikrofon tidak ditemukan. Saya akan terus mencoba.'
      }
    }

    rec.onend = () => {
      if (rec !== recognition) return
      recognitionRunning = false
      scheduleRestart()
    }

    return rec
  }

  function startRecognition() {
    if (!enabled.value || !sttSupported) return
    if (phase.value === 'bicara' || phase.value === 'memproses') return
    wantRecognition = true
    if (recognitionRunning) return
    // A fresh instance every restart, not a reused one: Chrome's
    // SpeechRecognition quietly stops producing results after a few
    // start/stop cycles on the same object, which is exactly the "works once,
    // never again" bug this session is chasing.
    recognition = createRecognition()
    if (!recognition) return
    try {
      recognition.start()
      recognitionRunning = true
    } catch {
      // Chrome throws InvalidStateError when start() races an session that is
      // still winding down; the restart loop picks it up on the next tick.
      recognitionRunning = false
      scheduleRestart()
    }
  }

  function stopRecognition() {
    wantRecognition = false
    clearTimeout(restartTimer)
    if (!recognition) return
    const rec = recognition
    recognition = null
    // Detach handlers first: abort()'s 'end' event fires asynchronously, and
    // by then `rec` may no longer be the instance we're tracking.
    rec.onstart = null
    rec.onresult = null
    rec.onerror = null
    rec.onend = null
    try {
      rec.abort()
    } catch {
      // Already stopped - nothing to undo.
    }
    recognitionRunning = false
  }

  function scheduleRestart() {
    if (!wantRecognition || !enabled.value) return
    clearTimeout(restartTimer)
    // Quick after a normal timeout; backing off to 5 s while the network or
    // the microphone is failing, so we don't spin but never give up either.
    const delay = failures ? Math.min(1000 * failures, 5000) : 300
    restartTimer = setTimeout(() => {
      if (wantRecognition && !recognitionRunning) startRecognition()
    }, delay)
  }

  /**
   * Safety net under the restart loop. `onend` is not guaranteed - a hidden
   * tab, a command that threw, or a TTS engine that never reported finishing
   * can all leave the assistant deaf with nothing scheduled. Every few seconds
   * this makes sure that whenever we are not talking, we are listening.
   */
  function keepAlive() {
    if (!enabled.value) return
    const idle =
      !ttsSupported || (!window.speechSynthesis.speaking && !window.speechSynthesis.pending)

    if (
      (phase.value === 'bicara' || phase.value === 'memproses') &&
      idle &&
      Date.now() - phaseSince > STUCK_MS
    ) {
      phase.value = 'siaga'
    }

    if ((phase.value === 'siaga' || phase.value === 'mendengar') && !recognitionRunning) {
      startRecognition()
    }
  }

  // ---- Alur perintah ----

  /**
   * Every final transcript goes to the Python NLP service (`lib/voiceNlp.ts`);
   * nothing is interpreted here. While idle that includes background chatter -
   * the server is what decides whether "Oke NearBy" was said.
   */
  async function handleTranscript(transcript: string) {
    if (phase.value === 'bicara' || phase.value === 'memproses') return
    const awake = phase.value === 'mendengar'

    if (awake) {
      // Already woken: whatever was said is the command. Close the microphone
      // while the server thinks, and don't let the command window run out
      // mid-request.
      clearTimeout(commandTimer)
      phase.value = 'memproses'
      stopRecognition()
    }

    const cmd = await interpret(transcript, !awake)
    if (!enabled.value) return
    // While idle the microphone stays open during the request, so another
    // utterance may already have woken us - first answer wins.
    if (!awake && phase.value !== 'siaga') return

    if (cmd.action === 'abaikan') {
      if (awake) backToListening('mendengar')
      return // background chatter, stay asleep
    }

    heard.value = transcript

    if (cmd.action === 'siaga_perintah') {
      // Just the wake word - answer and give them a window to speak.
      await speak(cmd.message || 'Ya, silakan.')
      if (!enabled.value) return
      backToListening('mendengar')
      clearTimeout(commandTimer)
      commandTimer = setTimeout(() => {
        if (phase.value === 'mendengar') void reply('Saya kembali siaga.')
      }, COMMAND_WINDOW_MS)
      return
    }

    await runCommand(cmd)
  }

  function backToListening(next: 'siaga' | 'mendengar') {
    phase.value = next
    startRecognition()
  }

  /**
   * Read the server's sentence *while* the page and data load, rather than
   * leaving the user in silence first. Resolves with the work's result once
   * both are done; a failure in `work` still waits for the sentence to finish.
   */
  async function announceWhile<T>(message: string, work: () => Promise<T>): Promise<T> {
    const talking = message ? speak(message) : Promise.resolve()
    try {
      return await work()
    } finally {
      await talking
    }
  }

  async function runCommand(cmd: VoiceCommand) {
    phase.value = 'memproses'
    stopRecognition()

    try {
      await dispatch(cmd)
    } catch {
      // A failed fetch or navigation must not strand us in 'memproses' with
      // the microphone off.
      if (enabled.value) await reply('Maaf, terjadi kesalahan. Silakan coba lagi.')
    }
  }

  async function dispatch(cmd: VoiceCommand) {
    switch (cmd.action) {
      case 'cari':
        await runSearch(cmd)
        break
      case 'buka':
        await runOpen(cmd)
        break
      case 'daftar':
        await runDirectory(cmd)
        break
      case 'halaman':
        await runOpenPage(cmd)
        break
      case 'favorit':
        await runFavorites(cmd)
        break
      case 'beranda':
        await router.push({ name: 'beranda' })
        await reply(cmd.message || 'Kembali ke halaman utama.')
        break
      case 'ulangi':
        await reply(spoken.value || 'Belum ada yang bisa saya ulangi.')
        break
      case 'berhenti':
        cancelSpeech()
        await reply(cmd.message || 'Baik, saya berhenti.')
        break
      case 'bantuan':
        await reply(cmd.message || HELP_TEXT)
        break
      case 'tidak_dikenal':
        await reply(
          cmd.message ||
            `Maaf, saya belum mengerti "${cmd.raw}". Ucapkan bantuan untuk mendengar daftar perintah.`,
        )
        break
      default:
        // Wake-word answers are handled before we get here.
        backToListening('siaga')
    }
  }

  async function runSearch(cmd: VoiceCommand) {
    const umkm = useUmkmStore()

    // "Baik, mencari makanan di balikpapan selatan." plays while the directory
    // opens and fills in. The page is opened regardless of whether the fetch
    // succeeds - otherwise a slow or failed load leaves the assistant only
    // talking, which reads as "it can't open pages".
    const loaded = await announceWhile(cmd.message, async () => {
      await router.push({ name: 'daftar' })
      await umkm.fetchAll()
      // Drive the real directory filters so the page matches what is spoken.
      umkm.cat = cmd.category ?? 'Semua'
      umkm.loc = cmd.location ?? 'Semua'
      umkm.q = cmd.keyword
      return !umkm.error
    })
    if (!loaded) {
      await reply('Saya sudah membuka daftar UMKM, tapi datanya belum berhasil dimuat. Coba lagi sebentar lagi.')
      return
    }

    let list = umkm.filteredDirectory
    let relaxed = false
    // A misheard word in the free-text part shouldn't sink an otherwise good
    // search - fall back to the category and kecamatan alone and say so.
    if (!list.length && cmd.keyword && (cmd.category || cmd.location)) {
      umkm.q = ''
      list = umkm.filteredDirectory
      relaxed = list.length > 0
    }

    results.value = list

    const what = cmd.category ? cmd.category.toLowerCase() : 'UMKM'
    const where = cmd.location ?? 'Balikpapan'

    if (!list.length) {
      await reply(`Maaf, saya tidak menemukan ${what} di ${where}. Coba sebutkan kategori atau wilayah lain.`)
      return
    }

    const spokenList = list.slice(0, SPOKEN_RESULTS)
    const intro = relaxed
      ? `Saya tidak menemukan yang persis seperti itu, tapi ada ${list.length} ${what} di ${where}.`
      : `Saya menemukan ${list.length} ${what} di ${where}.`
    const tail =
      list.length > spokenList.length
        ? ` Itu ${spokenList.length} teratas dari ${list.length}.`
        : ''

    await reply(
      `${intro} ${spokenList.map((u, i) => describeOne(u, i + 1)).join(' ')}${tail}` +
        ' Sebutkan buka nomor satu untuk mendengar detailnya.',
    )
  }

  /** "buka daftar UMKM", "buka direktori" - the whole catalog, no filters. */
  async function runDirectory(cmd: VoiceCommand) {
    const umkm = useUmkmStore()

    // Same as runSearch: speak while the page opens and the data loads.
    const loaded = await announceWhile(cmd.message, async () => {
      await router.push({ name: 'daftar' })
      await umkm.fetchAll()
      umkm.cat = 'Semua'
      umkm.loc = 'Semua'
      umkm.q = ''
      return !umkm.error
    })
    if (!loaded) {
      await reply('Saya sudah membuka daftar UMKM, tapi datanya belum berhasil dimuat. Coba lagi sebentar lagi.')
      return
    }

    const list = umkm.filteredDirectory
    results.value = list

    const spokenList = list.slice(0, SPOKEN_RESULTS)
    const tail = list.length > spokenList.length ? ` Itu ${spokenList.length} teratas dari ${list.length}.` : ''
    await reply(
      `Menampilkan seluruh daftar UMKM, ada ${list.length}. ` +
        `${spokenList.map((u, i) => describeOne(u, i + 1)).join(' ')}${tail}` +
        ' Sebutkan buka nomor satu untuk mendengar detailnya, atau sebutkan kategori untuk mempersempit.',
    )
  }

  /** "buka halaman panduan", "tentang kami", "syarat dan ketentuan" - a named static page. */
  async function runOpenPage(cmd: VoiceCommand) {
    const page = cmd.page
    if (!page) {
      await reply(`Maaf, saya belum mengerti halaman yang dimaksud. Ucapkan bantuan untuk mendengar daftar perintah.`)
      return
    }
    // Checked here, not on the server: only the browser knows who is logged in.
    if (page === 'akun' && useAuthStore().isGuest) {
      await reply('Halaman akun hanya ada setelah Anda masuk ke akun.')
      return
    }
    await router.push({ name: page })
    await reply(cmd.message || `Membuka halaman ${PAGE_LABELS[page]}.`)
  }

  async function runOpen(cmd: VoiceCommand) {
    if (!results.value.length) {
      await reply('Belum ada hasil pencarian. Sebutkan dulu, misalnya, carikan aku makanan di Balikpapan Selatan.')
      return
    }

    const position = cmd.index ?? 1
    const target = results.value[position - 1]
    if (!target) {
      await reply(`Nomor ${position} tidak ada. Hasil pencarian hanya sampai nomor ${results.value.length}.`)
      return
    }

    // The list rows carry no address, hours or menu; fetch the full record so
    // the detail actually adds something over what was already read out.
    const umkm = useUmkmStore()
    const detail = await announceWhile(cmd.message, async () => {
      await router.push({ name: 'detail', params: { id: String(target.id) } })
      return umkm.fetchDetail(target.id)
    })
    await reply(describeDetail(detail ?? target))
  }

  async function runFavorites(cmd: VoiceCommand) {
    const auth = useAuthStore()
    if (auth.isGuest) {
      await reply('Daftar favorit hanya ada setelah Anda masuk ke akun.')
      return
    }

    const umkm = useUmkmStore()
    await announceWhile(cmd.message, async () => {
      await umkm.fetchAll()
      await umkm.fetchFavorites()
      await router.push({ name: 'favorit' })
    })

    const list = umkm.favList
    results.value = list
    if (!list.length) {
      await reply('Daftar favorit Anda masih kosong.')
      return
    }
    await reply(
      `Ada ${list.length} UMKM favorit. ` +
        list.slice(0, SPOKEN_RESULTS).map((u, i) => describeOne(u, i + 1)).join(' '),
    )
  }

  // ---- Hidup / mati ----

  /** Resolves true once a session reaches `start`, false if permission is refused. */
  function waitForMic(): Promise<boolean> {
    if (micConfirmed) return Promise.resolve(true)
    return new Promise((resolve) => {
      const settle = (ok: boolean) => {
        clearTimeout(timer)
        micWaiter = null
        resolve(ok)
      }
      // Engines that never fire `start` still get their greeting.
      const timer = setTimeout(() => settle(true), MIC_READY_TIMEOUT_MS)
      micWaiter = settle
    })
  }

  /**
   * Turn the assistant on. Must be called synchronously from a user gesture
   * (tap, click or key press) the first time: that is the only moment the
   * browser will prompt for the microphone and allow the page to talk. After
   * that the permission holds for the session and the restart loop reopens
   * the microphone on its own.
   *
   * The greeting waits until the microphone is confirmed open, so hearing it
   * means the assistant really is listening.
   */
  async function enable(greeting = ACTIVATION_GREETING) {
    if (!supported) {
      error.value = 'Peramban ini belum mendukung perintah suara. Coba Google Chrome atau Microsoft Edge.'
      announce(error.value)
      return
    }
    if (enabled.value) return
    error.value = ''
    failures = 0
    enabled.value = true
    phase.value = 'siaga'

    // Both before the first await - still inside the gesture.
    unlockSpeech()
    const micReady = waitForMic()
    startRecognition()

    clearInterval(keepAliveTimer)
    keepAliveTimer = setInterval(keepAlive, KEEPALIVE_MS)

    const ok = await micReady
    if (!ok || !enabled.value) return
    await reply(greeting)
  }

  function disable() {
    enabled.value = false
    wantRecognition = false
    clearTimeout(restartTimer)
    clearTimeout(commandTimer)
    clearInterval(keepAliveTimer)
    micWaiter?.(false)
    stopRecognition()
    cancelSpeech()
    recognition = null
    phase.value = 'mati'
  }

  /**
   * Say one line outside the listen/answer cycle.
   *
   * Used for the "switched off" confirmation: `disable()` cancels speech and
   * closes the microphone, so the farewell has to be queued *after* teardown,
   * and it must not restart recognition the way `reply()` would.
   */
  function announce(text: string) {
    if (!ttsSupported) return
    spoken.value = text
    const utterance = new SpeechSynthesisUtterance(text)
    utterance.lang = 'id-ID'
    utterance.rate = useA11yStore().speechRate
    window.speechSynthesis.speak(utterance)
  }

  return {
    supported,
    enabled,
    phase,
    listening,
    error,
    heard,
    spoken,
    results,
    enable,
    disable,
    announce,
    speak,
    /** Exposed for tests and for the keyboard shortcut's "what did you say?" path. */
    handleTranscript,
  }
})
