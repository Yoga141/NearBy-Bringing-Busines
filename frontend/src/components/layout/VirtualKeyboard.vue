<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useA11yStore } from '@/stores/a11y'
import CloseIcon from '@/components/shared/CloseIcon.vue'
import ShiftIcon from '@/components/shared/ShiftIcon.vue'
import BackspaceIcon from '@/components/shared/BackspaceIcon.vue'
import EnterKeyIcon from '@/components/shared/EnterKeyIcon.vue'

const a11y = useA11yStore()

type Field = HTMLInputElement | HTMLTextAreaElement

/** The field the visitor last focused — where the taps get typed. */
const target = ref<Field | null>(null)
const shift = ref(false)

const ROWS = [
  ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'],
  ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
  ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l'],
  ['z', 'x', 'c', 'v', 'b', 'n', 'm', '@', '.'],
]

const rows = computed(() => (shift.value ? ROWS.map((r) => r.map((k) => k.toUpperCase())) : ROWS))

function isField(el: EventTarget | null): el is Field {
  return el instanceof HTMLInputElement || el instanceof HTMLTextAreaElement
}

function onFocusIn(e: FocusEvent) {
  if (isField(e.target)) target.value = e.target
}

onMounted(() => {
  document.addEventListener('focusin', onFocusIn)
  if (isField(document.activeElement)) target.value = document.activeElement
})
onBeforeUnmount(() => document.removeEventListener('focusin', onFocusIn))

/**
 * Replaces the field's selection with `text` (or deletes backwards when
 * `text` is empty), then fires an `input` event so `v-model` picks it up —
 * assigning `.value` alone is invisible to Vue.
 *
 * The selection API isn't available on every input type (`email`, `number`
 * and friends report a null caret and throw on setSelectionRange), so those
 * fall back to appending at / trimming from the end.
 */
function write(text: string, deleteBackwards = false) {
  const el = target.value
  if (!el) return

  const hasCaret = el.selectionStart !== null
  const start = hasCaret ? (el.selectionStart as number) : el.value.length
  const end = hasCaret ? (el.selectionEnd as number) : start
  let from = start

  if (deleteBackwards && start === end) {
    if (start === 0) return
    from = start - 1
  }

  el.value = el.value.slice(0, from) + text + el.value.slice(end)

  if (hasCaret) {
    const caret = from + text.length
    try {
      el.setSelectionRange(caret, caret)
    } catch {
      // Input type doesn't support a caret after all — the text still landed.
    }
  }

  el.dispatchEvent(new Event('input', { bubbles: true }))
  el.focus()
}

function press(key: string) {
  write(key)
  // Behave like a phone keyboard: one shifted character, then back to lower.
  if (shift.value) shift.value = false
}

function submit() {
  const el = target.value
  if (!el) return
  el.dispatchEvent(new Event('change', { bubbles: true }))
  el.closest('form')?.requestSubmit()
}

const keyClass =
  'flex h-11 min-w-9 flex-1 items-center justify-center rounded-[9px] border border-border-input bg-white text-[15px] font-bold text-brand-navy transition-colors duration-150 hover:bg-surface-alt active:bg-brand-blue-tint'
</script>

<template>
  <div
    class="fixed bottom-0 left-0 z-[80] w-full border-t border-border-card bg-cream/95 px-3 pt-2.5 pb-3 shadow-[0_-10px_30px_rgba(9,24,40,.14)] backdrop-blur-[10px]"
    role="group"
    aria-label="Keyboard virtual"
  >
    <div class="mx-auto max-w-[760px]">
      <div class="mb-2 flex items-center gap-2.5 px-1">
        <span class="text-[11.5px] font-extrabold tracking-[.06em] text-text-faint uppercase">Keyboard virtual</span>
        <span v-if="!target" class="text-[12px] font-semibold text-gold">Pilih kolom teks dulu</span>
        <button
          type="button"
          class="ml-auto flex items-center gap-1 text-[12.5px] font-bold text-text-muted hover:text-brand-navy"
          @mousedown.prevent
          @click="a11y.virtualKeyboard = false"
        >
          Tutup <CloseIcon size="12px" />
        </button>
      </div>

      <div v-for="(row, ri) in rows" :key="ri" class="mb-1.5 flex gap-1.5">
        <button
          v-for="k in row"
          :key="k"
          type="button"
          :class="keyClass"
          :disabled="!target"
          @mousedown.prevent
          @click="press(k)"
        >
          {{ k }}
        </button>
      </div>

      <div class="flex gap-1.5">
        <button
          type="button"
          class="flex h-11 flex-none items-center justify-center gap-1 rounded-[9px] border border-border-input px-4 text-[13px] font-extrabold transition-colors duration-150"
          :class="shift ? 'bg-brand-blue text-white' : 'bg-white text-brand-navy hover:bg-surface-alt'"
          @mousedown.prevent
          @click="shift = !shift"
        >
          <ShiftIcon size="13px" /> Shift
        </button>
        <button type="button" :class="keyClass" :disabled="!target" @mousedown.prevent @click="press(' ')">Spasi</button>
        <button
          type="button"
          class="flex h-11 flex-none items-center justify-center gap-1 rounded-[9px] border border-border-input bg-white px-4 text-[13px] font-extrabold text-brand-navy hover:bg-surface-alt"
          :disabled="!target"
          @mousedown.prevent
          @click="write('', true)"
        >
          <BackspaceIcon size="14px" /> Hapus
        </button>
        <button
          type="button"
          class="flex h-11 flex-none items-center justify-center gap-1 rounded-[9px] bg-brand-navy px-4 text-[13px] font-extrabold text-white"
          :disabled="!target"
          @mousedown.prevent
          @click="submit"
        >
          <EnterKeyIcon size="14px" /> Enter
        </button>
      </div>
    </div>
  </div>
</template>
