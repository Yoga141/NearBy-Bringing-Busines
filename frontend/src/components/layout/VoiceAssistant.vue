<script setup lang="ts">
/**
 * Headless mount point for the voice assistant (`stores/voice.ts`).
 *
 * Renders no visible UI on purpose — the feature is meant to be usable with the
 * screen switched off entirely. What it does render is a visually hidden
 * aria-live region, so a user already running NVDA or TalkBack gets the same
 * answer through their own screen reader instead of only through our
 * `speechSynthesis` voice.
 *
 * It also owns the two ways in: the Alt+V shortcut (reachable without sight)
 * and the persisted preference set from the accessibility panel. Switching the
 * microphone on needs a user gesture the first time, so when a returning
 * visitor has it enabled we try immediately and, if the browser refuses, arm a
 * one-shot listener that starts it on their first key press or tap.
 */
import { onBeforeUnmount, onMounted, watch } from 'vue'
import { useA11yStore } from '@/stores/a11y'
import { useVoiceStore } from '@/stores/voice'

const a11y = useA11yStore()
const voice = useVoiceStore()

function onKeydown(event: KeyboardEvent) {
  // Alt+V: toggle. Deliberately not a bare key — it must stay usable while a
  // text field has focus.
  if (event.altKey && !event.ctrlKey && !event.metaKey && event.key.toLowerCase() === 'v') {
    event.preventDefault()
    a11y.voiceAssistant = !a11y.voiceAssistant
  }
}

/** Start on the visitor's next interaction, when autostart was blocked. */
function armGestureStart() {
  const start = () => {
    window.removeEventListener('pointerdown', start)
    window.removeEventListener('keydown', start)
    if (a11y.voiceAssistant && !voice.enabled) void voice.enable()
  }
  window.addEventListener('pointerdown', start, { once: true })
  window.addEventListener('keydown', start, { once: true })
}

watch(
  () => a11y.voiceAssistant,
  (on) => {
    if (on && !voice.enabled) void voice.enable()
    if (!on && voice.enabled) {
      voice.disable()
      // Switching off must be audible too, or someone using Alt+V without the
      // screen cannot tell the assistant apart from one that simply stopped
      // responding.
      voice.announce('Asisten suara dimatikan.')
    }
  },
)

// The store turns itself off when the microphone permission is denied; keep the
// stored preference honest so the panel doesn't claim it is still on.
watch(
  () => voice.enabled,
  (on) => {
    if (!on && a11y.voiceAssistant) a11y.voiceAssistant = false
  },
)

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
  if (a11y.voiceAssistant && voice.supported) {
    void voice.enable()
    armGestureStart()
  }
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  voice.disable()
})
</script>

<template>
  <!-- sr-only: present for assistive tech, invisible and unfocusable for everyone else. -->
  <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">{{ voice.spoken }}</div>
  <div class="sr-only" role="status" aria-live="polite">{{ voice.error }}</div>
</template>
