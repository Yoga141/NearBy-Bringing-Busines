<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M3 21L9 9C13 9.5 16 12.5 16.5 16.5L3 21Z" fill="currentColor" />
    <path d="M13 5L14 3M17 8L19.5 7M15 3L16.5 5.5M20 10.5L22 11" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
    <circle cx="19" cy="4" r="0.9" fill="currentColor" />
    <circle cx="21" cy="8" r="0.7" fill="currentColor" />
  </svg>
</template>
