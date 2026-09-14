import { ref, watch } from 'vue'
import { defineStore } from 'pinia'

const STORAGE_KEY = 'nearby_a11y'

interface StoredPrefs {
  speechRate?: number
  virtualKeyboard?: boolean
  slowMotion?: boolean
}

function readPrefs(): StoredPrefs {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}') as StoredPrefs
  } catch {
    // Blocked or corrupt storage just means we fall back to the defaults.
    return {}
  }
}

/**
 * Accessibility preferences: screen reading, an on-screen keyboard, and a
 * calmer motion setting. Persisted per browser so a visitor who needs them
 * doesn't have to switch them on again every visit.
 */
export const useA11yStore = defineStore('a11y', () => {
  const stored = readPrefs()

  const panelOpen = ref(false)
  /** 0.5x–2.0x, matching the slider in the design (default 1.0x). */
  const speechRate = ref(stored.speechRate ?? 1)
  const virtualKeyboard = ref(stored.virtualKeyboard ?? false)
  /** On by default — the design ships the calmer-motion setting enabled. */
  const slowMotion = ref(stored.slowMotion ?? true)

  const speaking = ref(false)
  const speechSupported = typeof window !== 'undefined' && 'speechSynthesis' in window

  function togglePanel() {
    panelOpen.value = !panelOpen.value
  }

  // ---- Bacakan halaman ----

  /**
   * Visible text of the current page, in reading order. Prefers <main> so the
   * header, footer and the floating widgets themselves aren't read out.
   */
  function pageText(): string {
    const scope =
      document.querySelector('main') ??
      document.querySelector('[data-a11y-read]') ??
      document.body
    return (scope as HTMLElement).innerText.replace(/\s+/g, ' ').trim()
  }

  function stopSpeaking() {
    if (!speechSupported) return
    window.speechSynthesis.cancel()
    speaking.value = false
  }

  /** Reads the page aloud, or stops a reading already in progress. */
  function toggleSpeaking() {
    if (!speechSupported) return
    if (speaking.value) {
      stopSpeaking()
      return
    }

    const text = pageText()
    if (!text) return

    // Cancel anything queued from a previous page before starting.
    window.speechSynthesis.cancel()

    // Long pages get cut off by some engines, so read in sentence-sized
    // chunks; `speaking` flips back off when the last chunk ends.
    const chunks = text.match(/[^.!?]+[.!?]*\s*/g) ?? [text]
    const utterances = chunks.map((chunk) => {
      const u = new SpeechSynthesisUtterance(chunk)
      u.lang = 'id-ID'
      u.rate = speechRate.value
      return u
    })

    const last = utterances[utterances.length - 1]
    last.onend = () => {
      speaking.value = false
    }
    last.onerror = () => {
      speaking.value = false
    }

    speaking.value = true
    for (const u of utterances) window.speechSynthesis.speak(u)
  }

  // A rate change applies to the next reading — restarting mid-sentence is
  // more disorienting than finishing the current one.
  watch(speechRate, () => {
    persist()
  })

  // ---- Animasi lambat & halus ----

  function applyMotion() {
    if (typeof document === 'undefined') return
    if (slowMotion.value) document.documentElement.dataset.slowmo = 'on'
    else delete document.documentElement.dataset.slowmo
  }

  watch(slowMotion, () => {
    applyMotion()
    persist()
  })
  applyMotion()

  // ---- Keyboard virtual ----

  watch(virtualKeyboard, () => persist())

  function persist() {
    try {
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
          speechRate: speechRate.value,
          virtualKeyboard: virtualKeyboard.value,
          slowMotion: slowMotion.value,
        } satisfies StoredPrefs),
      )
    } catch {
      // Preferences simply won't survive a reload if storage is unavailable.
    }
  }

  return {
    panelOpen,
    togglePanel,
    speechRate,
    speaking,
    speechSupported,
    toggleSpeaking,
    stopSpeaking,
    virtualKeyboard,
    slowMotion,
  }
})
