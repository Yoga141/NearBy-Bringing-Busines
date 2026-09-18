import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import router from '@/router'
import { useUmkmStore, type EnrichedUmkm } from './umkm'
import { useAuthStore } from './auth'
import { useA11yStore } from './a11y'
import { parseCommand, stripWakeWord, type ParsedCommand, type PageName } from '@/lib/voiceIntent'

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
 *     long session - see `scheduleRestart`.
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

      for (const u of utterances) window.speechSynthesis.speak(u)
    })
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

    rec.onerror = (event) => {
      // 'no-speech' and 'aborted' are the normal rhythm of a restart loop, not
      // failures worth telling the user about.
      if (event.error === 'no-speech' || event.error === 'aborted') return
      if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
        error.value = 'Izin mikrofon ditolak. Aktifkan izin mikrofon di peramban untuk memakai asisten suara.'
        disable()
        return
      }
      if (event.error === 'network') {
        error.value = 'Pengenalan suara butuh koneksi internet.'
      }
    }

    rec.onend = () => {
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
    restartTimer = setTimeout(() => {
      if (wantRecognition && !recognitionRunning) startRecognition()
    }, 400)
  }

  // ---- Alur perintah ----

  async function handleTranscript(transcript: string) {
    if (phase.value === 'bicara' || phase.value === 'memproses') return
    heard.value = transcript

    if (phase.value === 'mendengar') {
      // Already woken: whatever was said is the command.
      clearTimeout(commandTimer)
      await runCommand(parseCommand(transcript))
      return
    }

    const afterWake = stripWakeWord(transcript)
    if (afterWake === null) return // background chatter, stay asleep

    if (!afterWake) {
      // Just the wake word - answer and give them a window to speak.
      await speak('Ya, silakan.')
      if (!enabled.value) return
      phase.value = 'mendengar'
      startRecognition()
      clearTimeout(commandTimer)
      commandTimer = setTimeout(() => {
        if (phase.value === 'mendengar') void reply('Saya kembali siaga.')
      }, COMMAND_WINDOW_MS)
      return
    }

    // Wake word and command in one breath: "Oke NearBy, carikan aku makanan…"
    await runCommand(parseCommand(afterWake))
  }

  async function runCommand(cmd: ParsedCommand) {
    phase.value = 'memproses'
    stopRecognition()

    switch (cmd.intent) {
      case 'cari':
        await runSearch(cmd)
        break
      case 'buka':
        await runOpen(cmd)
        break
      case 'daftar':
        await runDirectory()
        break
      case 'halaman':
        await runOpenPage(cmd.page)
        break
      case 'favorit':
        await runFavorites()
        break
      case 'beranda':
        await router.push({ name: 'beranda' })
        await reply('Kembali ke halaman utama.')
        break
      case 'ulangi':
        await reply(spoken.value || 'Belum ada yang bisa saya ulangi.')
        break
      case 'berhenti':
        cancelSpeech()
        await reply('Baik, saya berhenti.')
        break
      case 'bantuan':
        await reply(HELP_TEXT)
        break
      case 'tidak_dikenal':
        await reply(
          `Maaf, saya belum mengerti "${cmd.raw}". Ucapkan bantuan untuk mendengar daftar perintah.`,
        )
        break
    }
  }

  async function runSearch(cmd: ParsedCommand) {
    const umkm = useUmkmStore()

    // Open the page first, independent of whether the fetch below succeeds -
    // otherwise a slow or failed load leaves the assistant only talking, with
    // nothing to show for it, which reads as "it can't open pages" even
    // though the command was understood just fine.
    await router.push({ name: 'daftar' })

    await umkm.fetchAll()
    if (umkm.error) {
      await reply('Saya sudah membuka daftar UMKM, tapi datanya belum berhasil dimuat. Coba lagi sebentar lagi.')
      return
    }

    // Drive the real directory filters so the page matches what is spoken.
    umkm.cat = cmd.category ?? 'Semua'
    umkm.loc = cmd.location ?? 'Semua'
    umkm.q = cmd.keyword

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
  async function runDirectory() {
    const umkm = useUmkmStore()

    // Same reasoning as runSearch: open the page before the fetch settles.
    await router.push({ name: 'daftar' })

    await umkm.fetchAll()
    if (umkm.error) {
      await reply('Saya sudah membuka daftar UMKM, tapi datanya belum berhasil dimuat. Coba lagi sebentar lagi.')
      return
    }

    umkm.cat = 'Semua'
    umkm.loc = 'Semua'
    umkm.q = ''

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
  async function runOpenPage(page: PageName | null) {
    if (!page) {
      await reply(`Maaf, saya belum mengerti halaman yang dimaksud. Ucapkan bantuan untuk mendengar daftar perintah.`)
      return
    }
    if (page === 'akun' && useAuthStore().isGuest) {
      await reply('Halaman akun hanya ada setelah Anda masuk ke akun.')
      return
    }
    await router.push({ name: page })
    await reply(`Membuka halaman ${PAGE_LABELS[page]}.`)
  }

  async function runOpen(cmd: ParsedCommand) {
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

    await router.push({ name: 'detail', params: { id: String(target.id) } })

    // The list rows carry no address, hours or menu; fetch the full record so
    // the detail actually adds something over what was already read out.
    const umkm = useUmkmStore()
    const detail = await umkm.fetchDetail(target.id)
    await reply(describeDetail(detail ?? target))
  }

  async function runFavorites() {
    const auth = useAuthStore()
    if (auth.isGuest) {
      await reply('Daftar favorit hanya ada setelah Anda masuk ke akun.')
      return
    }

    const umkm = useUmkmStore()
    await umkm.fetchAll()
    await umkm.fetchFavorites()
    await router.push({ name: 'favorit' })

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

  /**
   * Turn the assistant on. Must be called from a user gesture the first time:
   * the browser only prompts for microphone permission in response to one.
   */
  async function enable() {
    if (!supported) {
      error.value = 'Peramban ini belum mendukung perintah suara. Coba Google Chrome atau Microsoft Edge.'
      return
    }
    if (enabled.value) return
    error.value = ''
    enabled.value = true
    phase.value = 'siaga'
    startRecognition()
    await speak('Asisten suara aktif. Ucapkan Oke NearBy, lalu sebutkan perintah Anda.')
    if (enabled.value) {
      phase.value = 'siaga'
      startRecognition()
    }
  }

  function disable() {
    enabled.value = false
    wantRecognition = false
    clearTimeout(restartTimer)
    clearTimeout(commandTimer)
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
