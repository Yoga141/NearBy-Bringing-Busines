<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <circle cx="13" cy="13" r="8" fill="none" stroke="currentColor" stroke-width="1.6" />
    <circle cx="13" cy="13" r="3.4" fill="none" stroke="currentColor" stroke-width="1.4" />
    <path d="M4 3V9C4 10.1 4.9 11 6 11V21M4 3V9M6 3V9M8 3V9C8 10.1 7.1 11 6 11" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" />
  </svg>
</template>
