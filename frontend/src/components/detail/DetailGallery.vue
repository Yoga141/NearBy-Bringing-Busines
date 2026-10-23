<script setup lang="ts">
import { computed } from 'vue'
import PlaceholderThumb from '@/components/shared/PlaceholderThumb.vue'
import ImageIcon from '@/components/shared/ImageIcon.vue'
import ExpandIcon from '@/components/shared/ExpandIcon.vue'
import { useUiStore } from '@/stores/ui'
import type { UmkmPhoto } from '@/types'

const props = withDefaults(
  defineProps<{
    name: string
    photos: UmkmPhoto[]
    imgLabel?: string
  }>(),
  {
    imgLabel: '',
  },
)

const ui = useUiStore()

const main = computed(() => props.photos[0] ?? null)
const photo2 = computed(() => props.photos[1] ?? null)
const photo3 = computed(() => props.photos[2] ?? null)
const extra = computed(() => Math.max(0, props.photos.length - 3))

function zoom(photo: UmkmPhoto | null, fallbackCaption: string, index: number) {
  const caption = photo ? `${props.name} · foto ${index + 1}` : fallbackCaption
  ui.openLightbox(photo?.url ?? null, caption)
}
</script>

<template>
  <div
    class="mb-[26px] grid grid-rows-[220px_100px] gap-3 overflow-hidden tablet:h-[340px] tablet:grid-cols-[2fr_1fr] tablet:grid-rows-1"
  >
    <!-- Main Left Tile (2fr) -->
    <button
      type="button"
      class="group relative block h-full min-h-0 w-full min-w-0 cursor-zoom-in overflow-hidden rounded-[20px] border border-border-card text-left transition-all"
      :title="main ? 'Klik untuk perbesar' : `${name} · utama`"
      @click="zoom(main, `${imgLabel || name} · utama`, 0)"
    >
      <img
        v-if="main?.url"
        :src="main.url"
        :alt="`Foto utama ${name}`"
        loading="lazy"
        decoding="async"
        class="block h-full max-h-full w-full max-w-full object-cover object-center rounded-[20px] transition-transform duration-300 group-hover:scale-105"
      />
      <PlaceholderThumb
        v-else
        :icon="ImageIcon"
        :label="imgLabel ? `${imgLabel} · utama` : 'pemilik belum mengunggah foto'"
        variant="warm"
        rounded="rounded-[20px]"
      />
      <span class="absolute right-2.5 bottom-2.5 flex h-[26px] w-[26px] items-center justify-center rounded-lg bg-[rgba(15,30,45,.66)] text-white shadow-xs">
        <ExpandIcon size="13px" />
      </span>
    </button>

    <!-- Side Right Tiles (1fr) -->
    <div class="grid min-h-0 min-w-0 grid-cols-2 grid-rows-1 gap-3 tablet:grid-cols-1 tablet:grid-rows-2">
      <!-- Tile 2: Galeri 2 -->
      <button
        type="button"
        class="group relative block h-full min-h-0 w-full min-w-0 cursor-zoom-in overflow-hidden rounded-[20px] border border-border-card text-left transition-all"
        :title="photo2 ? 'Klik untuk perbesar' : 'Galeri 2'"
        @click="zoom(photo2, 'Galeri 2', 1)"
      >
        <img
          v-if="photo2?.url"
          :src="photo2.url"
          :alt="`Foto 2 ${name}`"
          loading="lazy"
          decoding="async"
          class="block h-full max-h-full w-full max-w-full object-cover object-center rounded-[20px] transition-transform duration-300 group-hover:scale-105"
        />
        <PlaceholderThumb
          v-else
          :icon="ImageIcon"
          label="galeri 2"
          variant="teal"
          rounded="rounded-[20px]"
        />
        <span class="absolute right-2 bottom-2 flex h-[22px] w-[22px] items-center justify-center rounded-md bg-[rgba(15,30,45,.66)] text-white shadow-xs">
          <ExpandIcon size="10px" />
        </span>
      </button>

      <!-- Tile 3: Galeri 3 -->
      <button
        type="button"
        class="group relative block h-full min-h-0 w-full min-w-0 cursor-zoom-in overflow-hidden rounded-[20px] border border-border-card text-left transition-all"
        :title="photo3 ? 'Klik untuk perbesar' : 'Galeri 3'"
        @click="zoom(photo3, 'Galeri 3', 2)"
      >
        <img
          v-if="photo3?.url"
          :src="photo3.url"
          :alt="`Foto 3 ${name}`"
          loading="lazy"
          decoding="async"
          class="block h-full max-h-full w-full max-w-full object-cover object-center rounded-[20px] transition-transform duration-300 group-hover:scale-105"
        />
        <PlaceholderThumb
          v-else
          :icon="ImageIcon"
          label="galeri 3"
          variant="blue"
          rounded="rounded-[20px]"
        />
        <span
          v-if="extra"
          class="absolute inset-0 flex items-center justify-center rounded-[20px] bg-[rgba(15,30,45,.55)] text-lg font-extrabold text-white backdrop-blur-[1px]"
        >
          +{{ extra }} foto
        </span>
        <span
          v-else
          class="absolute right-2 bottom-2 flex h-[22px] w-[22px] items-center justify-center rounded-md bg-[rgba(15,30,45,.66)] text-white shadow-xs"
        >
          <ExpandIcon size="10px" />
        </span>
      </button>
    </div>
  </div>
</template>
