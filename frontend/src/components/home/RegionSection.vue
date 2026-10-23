<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useUmkmStore } from '@/stores/umkm'
import { REGION_VISUALS } from './RegionIcons'

const router = useRouter()
const umkm = useUmkmStore()

function open(full: string) {
  umkm.filterByLocation(full)
  router.push({ name: 'daftar' })
}

function getVisual(full: string) {
  return (
    REGION_VISUALS[full] ?? {
      gradient: 'linear-gradient(145deg, #16324b 0%, #2c5ead 100%)',
      accentColor: '#93C5FD',
      iconSvg: '',
    }
  )
}
</script>

<template>
  <section class="mx-auto max-w-[1200px] px-6 pt-[54px] pb-5">
    <div v-reveal class="mb-[26px]">
      <div class="text-[13px] font-bold tracking-[.08em] text-gold uppercase">Berdasarkan lokasi</div>
      <h2 class="mt-1.5 text-[24px] font-extrabold tracking-[-.02em] mobile:text-[28px] tablet:text-[32px]">
        Jelajahi per wilayah
      </h2>
    </div>
    <div class="grid grid-cols-2 gap-3.5 mobile:grid-cols-3 tablet:grid-cols-6">
      <div
        v-for="(r, i) in umkm.regionCards"
        :key="r.full"
        v-reveal="{ delay: i * 60 }"
        class="group relative h-[155px] cursor-pointer overflow-hidden rounded-[20px] p-4 text-white shadow-[0_4px_16px_rgba(15,23,42,0.12)] transition-all duration-300 hover:-translate-y-1.5 hover:shadow-[0_16px_32px_rgba(15,23,42,0.22)]"
        :style="{ background: getVisual(r.full).gradient }"
        @click="open(r.full)"
      >
        <!-- Large subtle background icon watermark -->
        <div
          class="pointer-events-none absolute -right-3 -bottom-3 h-24 w-24 opacity-15 transition-transform duration-500 group-hover:scale-125 group-hover:opacity-25"
          :style="{ color: getVisual(r.full).accentColor }"
          v-html="getVisual(r.full).iconSvg"
        />

        <!-- Top Right Icon Badge -->
        <div class="flex items-center justify-between">
          <span
            class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15 p-2 backdrop-blur-sm transition-transform duration-300 group-hover:scale-110"
            :style="{ color: getVisual(r.full).accentColor }"
            v-html="getVisual(r.full).iconSvg"
          />
        </div>

        <!-- Card bottom info -->
        <div class="absolute bottom-3.5 left-4 right-4 z-10">
          <div class="text-[16.5px] font-extrabold tracking-tight">{{ r.short }}</div>
          <div class="mt-0.5 text-[12.5px] font-medium text-white/80">{{ r.count }} UMKM</div>
        </div>
      </div>
    </div>
  </section>
</template>
