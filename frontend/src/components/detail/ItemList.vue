<script setup lang="ts">
import { useUiStore } from '@/stores/ui'
import ExpandIcon from '@/components/shared/ExpandIcon.vue'
import { isPhotoUrl, resolveMenuIcon } from '@/lib/menuIcons'
import type { UmkmItem } from '@/types'

defineProps<{ items: UmkmItem[] }>()
const ui = useUiStore()

function available(item: UmkmItem) {
  return item.avail !== false
}

function zoom(item: UmkmItem) {
  if (isPhotoUrl(item.img)) {
    ui.openLightbox(item.img ?? null, item.name, 'plate')
  }
}
</script>

<template>
  <div class="mb-[30px] grid grid-cols-1 gap-3 mobile:grid-cols-2">
    <div
      v-for="it in items"
      :key="it.name"
      class="flex items-center gap-[13px] rounded-xl border border-border-card bg-white px-3.5 py-2.5"
    >
      <div
        class="relative h-[60px] w-[60px] flex-none overflow-hidden rounded-[10px]"
        :class="isPhotoUrl(it.img) ? 'cursor-zoom-in' : 'cursor-default'"
        :title="isPhotoUrl(it.img) ? 'Klik untuk perbesar foto' : it.name"
        @click="zoom(it)"
      >
        <!-- Foto asli jika ada -->
        <img
          v-if="isPhotoUrl(it.img)"
          :src="it.img!"
          :alt="it.name"
          loading="lazy"
          decoding="async"
          class="h-full w-full object-cover object-center transition-transform duration-200 hover:scale-105"
        />

        <!-- Default icon placeholder (makanan / minuman / kopi / mangkok dsb) -->
        <div
          v-else
          class="flex h-full w-full items-center justify-center border border-[#EDE7D9] bg-[#FBF8F1] text-brand-gold"
          :title="resolveMenuIcon(it.img, it.name).label"
        >
          <component :is="resolveMenuIcon(it.img, it.name).icon" size="26px" />
        </div>

        <span
          v-if="isPhotoUrl(it.img)"
          class="absolute right-[3px] bottom-[3px] flex h-[17px] w-[17px] items-center justify-center rounded-[6px] bg-[rgba(15,30,45,.66)] text-white"
        >
          <ExpandIcon size="10px" />
        </span>
      </div>

      <div class="flex min-w-0 flex-1 flex-col gap-[3px]">
        <div class="text-[14.5px] font-bold wrap-break-word">{{ it.name }}</div>
        <div v-if="it.price" class="text-[14px] font-extrabold whitespace-nowrap text-teal">{{ it.price }}</div>
      </div>
      <span
        class="flex-none rounded-full px-[9px] py-1 text-[10.5px] font-extrabold whitespace-nowrap"
        :style="
          available(it)
            ? { background: '#E3EFED', color: '#2E7D6E' }
            : { background: '#FBEEEA', color: '#C0472F' }
        "
      >
        {{ available(it) ? 'Tersedia' : 'Habis' }}
      </span>
    </div>
  </div>
</template>
