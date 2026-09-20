import { ref, watch } from 'vue'
import { defineStore } from 'pinia'
import { buildTextMap, clearHighlights, highlightSentence, highlightWord } from '@/lib/readAlong'

const STORAGE_KEY = 'nearby_a11y'

interface StoredPrefs {
  speechRate?: number
  virtualKeyboard?: boolean
  voiceAssistant?: boolean
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
 * Accessibility preferences: screen reading, an on-screen keyboard,
 * and the hands-free voice assistant. Persisted per browser so
 * a visitor who needs them doesn't have to switch them on again every visit -
 * which matters most for `voiceAssistant`, the one setting a blind user would
 * otherwise have to find on screen after every reload.
 */
export const useA11yStore = defineStore('a11y', () => {
  const stored = readPrefs()

  const panelOpen = ref(false)
  /** 0.5x–2.0x, matching the slider in the design (default 1.0x). */
  const speechRate = ref(stored.speechRate ?? 1)
  const virtualKeyboard = ref(stored.virtualKeyboard ?? false)
  /**
   * Hands-free voice assistant ("Oke NearBy"). Off by default: it holds the
   * microphone open, which is not something to switch on for someone who never
   * asked for it. `VoiceAssistant.vue` watches this and owns the lifecycle.
   */
  const voiceAssistant = ref(stored.voiceAssistant ?? false)

  const speaking = ref(false)
  const speechSupported = typeof window !== 'undefined' && 'speechSynthesis' in window

  function togglePanel() {
    panelOpen.value = !panelOpen.value
  }

  // ---- Bacakan halaman ----

  /**
   * Visible text of the current page, in reading order, plus a map back to the
   * DOM for the read-along highlight. Prefers <main> so the header, footer and
   * the floating widgets themselves aren't read out.
   */
  function pageScope(): Element {
    return document.querySelector('main') ?? document.querySelector('[data-a11y-read]') ?? document.body
  }

  /** Bumped on every start/stop so a cancelled reading can't touch a newer one. */
  let session = 0

  function stopSpeaking() {
    session++
    speaking.value = false
    clearHighlights()
    if (!speechSupported) return
    window.speechSynthesis.cancel()
  }

  /** Reads the page aloud, or stops a reading already in progress. */
  function toggleSpeaking() {
    if (!speechSupported) return
    if (speaking.value) {
      stopSpeaking()
      return
    }

    const map = buildTextMap(pageScope())
    const text = map.text
    if (!text) return

    // Cancel anything queued from a previous page before starting.
    window.speechSynthesis.cancel()
    clearHighlights()
    const mine = ++session

    // Long pages get cut off by some engines, so read in sentence-sized
    // chunks; `speaking` flips back off when the last chunk ends.
    const chunks = [...text.matchAll(/[^.!?]+[.!?]*\s*/g)]
    const utterances = chunks.map((m) => {
      const raw = m[0]
      const lead = raw.length - raw.trimStart().length
      const start = m.index ?? 0
      const u = new SpeechSynthesisUtterance(raw)
      u.lang = 'id-ID'
      u.rate = speechRate.value
      const trimmed = raw.trim().length

      // The whole sentence is highlighted as soon as it starts (engines
      // without word events still get this), and the current word on top of
      // it wherever the engine reports word boundaries.
      u.onstart = () => {
        if (session === mine) highlightSentence(map, start + lead, trimmed)
      }
      u.onboundary = (e) => {
        if (session !== mine || e.name === 'sentence') return
        let len = e.charLength
        if (!len) {
          const rest = raw.slice(e.charIndex).search(/\s/)
          len = rest === -1 ? raw.length - e.charIndex : rest
        }
        highlightWord(map, start + e.charIndex, len)
      }
      return u
    })

    const finish = () => {
      if (session !== mine) return
      speaking.value = false
      clearHighlights()
    }
    const last = utterances[utterances.length - 1]
    last.onend = finish
    last.onerror = finish

    speaking.value = true
    for (const u of utterances) window.speechSynthesis.speak(u)
  }

  // A rate change applies to the next reading - restarting mid-sentence is
  // more disorienting than finishing the current one.
  watch(speechRate, () => {
    persist()
  })

  // ---- Keyboard virtual ----

  watch(virtualKeyboard, () => persist())

  // ---- Asisten suara ----

  watch(voiceAssistant, () => persist())

  function persist() {
    try {
      localStorage.setItem(
        STORAGE_KEY,
        JSON.stringify({
          speechRate: speechRate.value,
          virtualKeyboard: virtualKeyboard.value,
          voiceAssistant: voiceAssistant.value,
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
    voiceAssistant,
  }
})
