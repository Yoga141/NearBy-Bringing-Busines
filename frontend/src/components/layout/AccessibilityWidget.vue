<script setup lang="ts">
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { useA11yStore } from '@/stores/a11y'
import { useVoiceStore } from '@/stores/voice'
import ToggleSwitch from '@/components/shared/ToggleSwitch.vue'
import PlayIcon from '@/components/shared/PlayIcon.vue'
import StopIcon from '@/components/shared/StopIcon.vue'
import CloseIcon from '@/components/shared/CloseIcon.vue'

const a11y = useA11yStore()
const voice = useVoiceStore()
const route = useRoute()

const PHASE_LABEL: Record<string, string> = {
  mati: 'Nonaktif',
  siaga: 'Siaga - ucapkan "Oke NearBy"',
  mendengar: 'Mendengarkan perintah…',
  memproses: 'Memproses…',
  bicara: 'Sedang menjawab…',
}

// speechSynthesis outlives the view it was reading, so a navigation mid-read
// would keep narrating the previous page over the new one. The voice assistant
// navigates *before* it answers, so this never truncates its replies.
watch(() => route.fullPath, () => a11y.stopSpeaking())
</script>

<template>
  <div class="fixed top-[86px] right-4 z-[70] flex flex-col items-end gap-3 mobile:right-[22px]">
    <button
      type="button"
      title="Aksesibilitas"
      aria-label="Buka menu aksesibilitas"
      :aria-expanded="a11y.panelOpen"
      class="flex h-12 w-12 items-center justify-center rounded-full border border-border-card bg-white text-brand-blue shadow-[0_12px_30px_rgba(19,50,77,.22)] transition-all hover:-translate-y-0.5 hover:bg-brand-blue-tint"
      @click="a11y.togglePanel"
    >
      <!-- Universal-access figure: head + outstretched arms and legs. -->
      <svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
        <circle cx="12" cy="4.4" r="1.9" fill="currentColor" stroke="none" />
        <path d="M4.5 8.4h15" />
        <path d="M12 8.4v5.2" />
        <path d="M12 13.6 8.8 21M12 13.6 15.2 21" />
      </svg>
    </button>

    <div
      v-if="a11y.panelOpen"
      class="w-[340px] max-w-[calc(100vw-44px)] overflow-hidden rounded-[18px] border border-border-card bg-white shadow-[0_22px_55px_rgba(9,24,40,.28)]"
    >
      <div class="flex items-center justify-between bg-brand-navy px-[18px] py-4 text-white">
        <div>
          <div class="text-[15.5px] font-extrabold">Aksesibilitas</div>
          <div class="mt-px text-xs text-[#AFC3DC]">Bantuan baca, teks &amp; keyboard</div>
        </div>
        <button type="button" aria-label="Tutup" class="text-[#AFC3DC]" @click="a11y.panelOpen = false">
          <CloseIcon size="18px" />
        </button>
      </div>

      <div class="px-[18px] pt-[18px] pb-[18px]">
        <!-- Bacakan halaman -->
        <div class="text-[12.5px] font-extrabold tracking-[.06em] text-text-faint uppercase">Bacakan halaman</div>

        <button
          type="button"
          class="mt-2.5 flex w-full items-center justify-center gap-2.5 rounded-[12px] px-4 py-3 text-[14.5px] font-extrabold text-white shadow-[0_6px_16px_rgba(44,94,173,.28)] transition-colors duration-150 disabled:cursor-not-allowed disabled:opacity-60"
          :class="a11y.speaking ? 'bg-danger' : 'bg-brand-blue hover:bg-brand-blue-deep'"
          :disabled="!a11y.speechSupported"
          @click="a11y.toggleSpeaking"
        >
          <StopIcon v-if="a11y.speaking" size="13px" /><PlayIcon v-else size="13px" />
          {{ a11y.speaking ? 'Hentikan pembacaan' : 'Bacakan isi layar' }}
        </button>

        <p v-if="!a11y.speechSupported" class="mt-2 text-[12px] leading-relaxed text-danger">
          Peramban ini belum mendukung pembacaan suara.
        </p>

        <div class="mt-4">
          <div class="flex items-center justify-between">
            <label for="a11y-rate" class="text-[13px] font-bold text-brand-navy">Kecepatan suara</label>
            <span class="text-[13px] font-extrabold text-brand-blue">{{ a11y.speechRate.toFixed(1) }}x</span>
          </div>
          <input
            id="a11y-rate"
            v-model.number="a11y.speechRate"
            type="range"
            min="0.5"
            max="2"
            step="0.1"
            class="mt-2 w-full accent-[#2C5EAD]"
          />
          <p class="mt-1.5 text-[12px] leading-relaxed text-text-faint">
            Kecepatan baru dipakai pada pembacaan berikutnya.
          </p>
        </div>

        <!-- Asisten suara -->
        <div class="mt-[18px] border-t border-border-divider-2 pt-[18px]">
          <div class="flex items-center gap-3">
            <div class="min-w-0">
              <div class="text-[14px] font-extrabold text-brand-navy">Asisten suara</div>
              <div class="mt-0.5 text-[12.5px] leading-relaxed text-text-muted">
                Bebas layar - ucapkan &ldquo;Oke NearBy&rdquo;
              </div>
            </div>
            <div class="ml-auto">
              <ToggleSwitch
                v-model="a11y.voiceAssistant"
                tone="teal"
                label="Asisten suara"
                :disabled="!voice.supported"
              />
            </div>
          </div>

          <p
            v-if="voice.enabled"
            class="mt-2 text-[12px] leading-relaxed font-semibold text-brand-blue"
            role="status"
            aria-live="polite"
          >
            {{ PHASE_LABEL[voice.phase] ?? voice.phase }}
          </p>
          <p v-else-if="!voice.supported" class="mt-2 text-[12px] leading-relaxed text-danger">
            Perintah suara belum didukung peramban ini. Coba Google Chrome atau Microsoft Edge.
          </p>
          <p v-else class="mt-2 text-[12px] leading-relaxed text-text-faint">
            Bisa juga dinyalakan dengan tombol Alt + V, tanpa perlu melihat layar.
          </p>

          <p v-if="voice.error" class="mt-1.5 text-[12px] leading-relaxed font-semibold text-danger">
            {{ voice.error }}
          </p>
        </div>

        <!-- Keyboard virtual -->
        <div class="mt-[18px] flex items-center gap-3 border-t border-border-divider-2 pt-[18px]">
          <div class="min-w-0">
            <div class="text-[14px] font-extrabold text-brand-navy">Keyboard virtual</div>
            <div class="mt-0.5 text-[12.5px] leading-relaxed text-text-muted">Mengetik cukup dengan klik / tap</div>
          </div>
          <div class="ml-auto">
            <ToggleSwitch v-model="a11y.virtualKeyboard" tone="teal" />
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
