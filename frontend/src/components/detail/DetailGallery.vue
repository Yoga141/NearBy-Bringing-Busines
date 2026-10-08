<script setup lang="ts">
import { computed } from 'vue'
import UmkmThumb from '@/components/shared/UmkmThumb.vue'
import ExpandIcon from '@/components/shared/ExpandIcon.vue'
import { useUiStore } from '@/stores/ui'
import type { UmkmPhoto } from '@/types'

const props = defineProps<{ name: string; photos: UmkmPhoto[] }>()

const ui = useUiStore()

const main = computed(() => props.photos[0] ?? null)
const side = computed(() => props.photos.slice(1, 3))
const extra = computed(() => Math.max(0, props.photos.length - 3))

function zoom(photo: UmkmPhoto | null, index: number) {
  ui.openLightbox(photo?.url ?? null, photo ? `${props.name} · foto ${index + 1}` : `${props.name} · belum ada foto`)
}
</script>

<template>
  <div
    class="mb-[26px] grid gap-3 overflow-hidden"
    :class="
      side.length
        ? 'grid-rows-[220px_100px] tablet:h-[340px] tablet:grid-cols-[2fr_1fr] tablet:grid-rows-1'
        : 'h-[220px] grid-rows-1 tablet:h-[340px]'
    "
  >
    <!--
      Every row track is explicit (fixed px or minmax(0,1fr) via grid-rows-1/2).
      An implicit "auto" row would size itself to the photo's natural height, so a
      large upload would blow the gallery up and spill over the content below.
    -->
    <button
      type="button"
      class="relative block h-full min-h-0 w-full min-w-0 overflow-hidden rounded-[20px] text-left"
      :class="main ? 'cursor-zoom-in' : 'cursor-default'"
      :title="main ? 'Klik untuk perbesar' : 'Pemilik belum mengunggah foto'"
      @click="main && zoom(main, 0)"
    >
      <UmkmThumb :src="main?.url" :alt="`Foto utama ${name}`" rounded="rounded-[20px]" label="pemilik belum mengunggah foto" />
      <span v-if="main" class="absolute right-2.5 bottom-2.5 flex h-[26px] w-[26px] items-center justify-center rounded-lg bg-[rgba(15,30,45,.66)] text-white">
        <ExpandIcon size="13px" />
      </span>
    </button>
    <div v-if="side.length" class="grid min-h-0 min-w-0 grid-cols-2 grid-rows-1 gap-3 tablet:grid-cols-1 tablet:grid-rows-2">
      <button
        v-for="(photo, i) in side"
        :key="photo.id"
        type="button"
        class="relative block h-full min-h-0 w-full min-w-0 cursor-zoom-in overflow-hidden rounded-[20px] text-left"
        title="Klik untuk perbesar"
        @click="zoom(photo, i + 1)"
      >
        <UmkmThumb :src="photo.url" :alt="`Foto ${i + 2} ${name}`" rounded="rounded-[20px]" variant="teal" />
        <span
          v-if="i === side.length - 1 && extra"
          class="absolute inset-0 flex items-center justify-center rounded-[20px] bg-[rgba(15,30,45,.55)] text-lg font-extrabold text-white"
        >
          +{{ extra }} foto
        </span>
      </button>
    </div>
  </div>
</template>
