<script setup lang="ts">
/**
 * Headless mount point for the voice assistant (`stores/voice.ts`).
 *
 * Renders no visible UI on purpose - the feature is meant to be usable with the
 * screen switched off entirely. What it does render is a visually hidden
 * aria-live region, so a user already running NVDA or TalkBack gets the same
 * answer through their own screen reader instead of only through our
 * `speechSynthesis` voice.
 *
 * It also owns the ways in. Browsers only open the microphone (and only let a
 * page talk) in response to a user gesture, and a blind visitor cannot hunt for
 * a small button - so the whole page is the button: the first tap anywhere, or
 * any key on the keyboard, switches the assistant on and it greets the user as
 * soon as the microphone is live. Alt+V and the accessibility panel toggle it
 * explicitly, and switching it off that way is remembered.
 */
import { onBeforeUnmount, onMounted, watch } from 'vue'
import { useA11yStore } from '@/stores/a11y'
import { useVoiceStore } from '@/stores/voice'

const a11y = useA11yStore()
const voice = useVoiceStore()

/**
 * Every way a first interaction can reach us. `click` is there for screen
 * readers: TalkBack and VoiceOver swallow the raw touch and only deliver the
 * double-tap as a click.
 */
const GESTURE_EVENTS = ['pointerdown', 'touchstart', 'click', 'keydown'] as const

function isToggleShortcut(event: Event): boolean {
  return (
    event instanceof KeyboardEvent &&
    event.altKey &&
    !event.ctrlKey &&
    !event.metaKey &&
    event.key.toLowerCase() === 'v'
  )
}

function onKeydown(event: KeyboardEvent) {
  // Alt+V: toggle. Deliberately not a bare key - it must stay usable while a
  // text field has focus.
  if (isToggleShortcut(event)) {
    event.preventDefault()
    a11y.voiceAssistant = !a11y.voiceAssistant
  }
}

let armed = false

/**
 * Runs synchronously inside the gesture - `voice.enable()` needs that to get
 * the microphone prompt and unlock speech before its first await.
 */
function onGesture(event: Event) {
  // Alt+V is handled by its own toggle; starting here too would switch the
  // assistant on and straight back off.
  if (isToggleShortcut(event)) return
  disarmGestureStart()
  if (a11y.voiceAssistant && !voice.enabled) void voice.enable()
}

function armGestureStart() {
  if (armed || !a11y.voiceAssistant) return
  armed = true
  // Capture phase, so a handler further down that stops propagation can't
  // keep the assistant from starting.
  for (const type of GESTURE_EVENTS) {
    window.addEventListener(type, onGesture, { capture: true, passive: true })
  }
}

function disarmGestureStart() {
  if (!armed) return
  armed = false
  for (const type of GESTURE_EVENTS) {
    window.removeEventListener(type, onGesture, { capture: true })
  }
}

watch(
  () => a11y.voiceAssistant,
  (on) => {
    if (on && !voice.enabled) void voice.enable()
    if (!on) {
      disarmGestureStart()
      if (voice.enabled) {
        voice.disable()
        // Switching off must be audible too, or someone using Alt+V without the
        // screen cannot tell the assistant apart from one that simply stopped
        // responding.
        voice.announce('Asisten suara dimatikan.')
      }
    }
  },
)

// The store switches itself off when the microphone is refused. That is not the
// user opting out, so leave the preference alone and let the next tap or key
// press try again.
watch(
  () => voice.enabled,
  (on) => {
    if (!on && a11y.voiceAssistant) armGestureStart()
  },
)

/** Leaving the page is the one deliberate "close" we honour without Alt+V. */
function onPageHide() {
  disarmGestureStart()
  voice.disable()
}

/** Back from the back/forward cache: the microphone is closed, so re-arm. */
function onPageShow(event: PageTransitionEvent) {
  if (event.persisted && a11y.voiceAssistant && !voice.enabled) armGestureStart()
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('pagehide', onPageHide)
  window.addEventListener('pageshow', onPageShow)
  armGestureStart()
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('pagehide', onPageHide)
  window.removeEventListener('pageshow', onPageShow)
  disarmGestureStart()
  voice.disable()
})
</script>

<template>
  <!-- sr-only: present for assistive tech, invisible and unfocusable for everyone else. -->
  <div class="sr-only" role="status" aria-live="polite" aria-atomic="true">{{ voice.spoken }}</div>
  <div class="sr-only" role="status" aria-live="polite">{{ voice.error }}</div>
</template>
