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
      class="flex items-center gap-[13px] rounded-xl border border-border-card bg-white px-3.5 py-2.5 transition-colors dark:bg-[#16222f] dark:border-border-card"
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

        <!-- Default icon placeholder (makanan / mangkok / minuman / kopi dsb) -->
        <div
          v-else
          class="flex h-full w-full items-center justify-center border border-[#EDE7D9] bg-[#FAF5EC] text-[#B07A1E] transition-colors dark:border-[#2b3a4c] dark:bg-[#1e2733] dark:text-[#E5B558]"
          :title="resolveMenuIcon(it.img, it.name).label"
        >
          <component :is="resolveMenuIcon(it.img, it.name).icon" size="26px" />
        </div>

        <span
          v-if="isPhotoUrl(it.img)"
          class="absolute right-[3px] bottom-[3px] flex h-[17px] w-[17px] items-center justify-center rounded-[6px] bg-[rgba(15,30,45,.66)] text-white shadow-xs"
        >
          <ExpandIcon size="10px" />
        </span>
      </div>

      <div class="flex min-w-0 flex-1 flex-col gap-[3px]">
        <div class="text-[14.5px] font-bold text-brand-navy wrap-break-word dark:text-text-primary">{{ it.name }}</div>
        <div v-if="it.price" class="text-[14px] font-extrabold whitespace-nowrap text-teal dark:text-[#62c3ae]">{{ it.price }}</div>
      </div>
      <span
        class="flex-none rounded-full px-[9px] py-1 text-[10.5px] font-extrabold whitespace-nowrap transition-colors"
        :class="
          available(it)
            ? 'bg-[#E3EFED] text-[#2E7D6E] dark:bg-[#173833] dark:text-[#62c3ae]'
            : 'bg-[#FBEEEA] text-[#C0472F] dark:bg-[#3b211d] dark:text-[#f08c77]'
        "
      >
        {{ available(it) ? 'Tersedia' : 'Habis' }}
      </span>
    </div>
  </div>
</template>
