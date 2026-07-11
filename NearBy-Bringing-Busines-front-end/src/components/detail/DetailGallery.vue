<script setup lang="ts">
import PlaceholderThumb from '@/components/shared/PlaceholderThumb.vue'

defineProps<{ imgLabel: string }>()
</script>

<template>
  <div class="mb-[26px] grid h-[220px] grid-cols-1 gap-3 tablet:h-[340px] tablet:grid-cols-[2fr_1fr]">
    <PlaceholderThumb :label="`${imgLabel} · utama`" variant="warm" rounded="rounded-[20px]" />
    <div class="grid grid-rows-2 gap-3">
      <PlaceholderThumb label="galeri 2" variant="teal" rounded="rounded-[20px]" />
      <PlaceholderThumb label="galeri 3" variant="blue" rounded="rounded-[20px]" />
    </div>
  </div>
</template>
