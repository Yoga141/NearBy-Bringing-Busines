<script setup lang="ts">
import { starsLabel } from '@/data/reviews'
import type { Review } from '@/types'

defineProps<{ reviews: Review[] }>()
</script>

<template>
  <div
    v-for="rv in reviews"
    :key="rv.id"
    class="flex gap-[13px] border-t border-[#F0EADD] py-4 first:border-t-0"
  >
    <div class="flex h-[42px] w-[42px] flex-none items-center justify-center rounded-full bg-brand-blue-tint font-extrabold text-brand-blue">
      {{ rv.initial }}
    </div>
    <div>
      <div class="flex flex-wrap items-center gap-2.5">
        <div class="font-bold">{{ rv.name }}</div>
        <div class="text-[13px] font-bold text-gold">{{ starsLabel(rv.stars) }}</div>
        <div class="text-[12.5px] font-semibold text-text-faint-3">{{ rv.date }}</div>
      </div>
      <p class="mt-1.5 text-[14.5px] leading-[1.55] text-text-secondary">{{ rv.text }}</p>
    </div>
  </div>
</template>
