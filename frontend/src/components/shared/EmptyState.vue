<script setup lang="ts">
import type { Component } from 'vue'
import SearchIcon from './SearchIcon.vue'

withDefaults(defineProps<{ icon?: Component; title: string; subtitle?: string }>(), {
  icon: () => SearchIcon,
})
</script>

<template>
  <div class="rounded-[18px] px-5 py-16 text-center text-text-faint">
    <component :is="icon" size="36px" class="mx-auto" />
    <div class="mt-2.5 font-bold text-brand-navy">{{ title }}</div>
    <div v-if="subtitle" class="mt-1">{{ subtitle }}</div>
    <div v-if="$slots.default" class="mt-5">
      <slot />
    </div>
  </div>
</template>
