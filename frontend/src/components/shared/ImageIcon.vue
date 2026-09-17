<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <rect x="3" y="4" width="18" height="16" rx="2" fill="none" stroke="currentColor" stroke-width="1.7" />
    <circle cx="8.5" cy="9.5" r="1.8" fill="currentColor" />
    <path d="M3 16L8.5 11L13 15L16.5 12L21 16.5V18C21 19.1 20.1 20 19 20H5C3.9 20 3 19.1 3 18V16Z" fill="currentColor" />
  </svg>
</template>
