<script setup lang="ts">
import { useUiStore } from '@/stores/ui'

const ui = useUiStore()
</script>

<template>
  <div
    v-if="ui.lightbox"
    class="fixed inset-0 z-[90] flex items-center justify-center bg-[rgba(11,20,30,.82)] p-6 backdrop-blur-[3px]"
    @click="ui.closeLightbox"
  >
    <div class="flex max-w-[560px] flex-col items-center gap-4" @click.stop>
      <div class="flex h-[320px] w-[320px] max-w-full flex-col items-center justify-center gap-2 rounded-2xl bg-[#1c2e3d] text-[#F3EEE4]">
        <span class="text-4xl">{{ ui.lightbox.label }}</span>
        <span class="text-sm">foto belum diunggah</span>
      </div>
      <div class="text-[16px] font-bold text-[#F3EEE4]">{{ ui.lightbox.caption }}</div>
      <button
        type="button"
        class="rounded-[11px] border border-white/30 bg-white/10 px-5 py-2.5 font-bold text-white"
        @click="ui.closeLightbox"
      >
        Tutup ✕
      </button>
    </div>
  </div>
</template>
