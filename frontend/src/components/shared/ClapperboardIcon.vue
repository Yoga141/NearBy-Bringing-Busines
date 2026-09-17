<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M3 9.5V19C3 19.55 3.45 20 4 20H20C20.55 20 21 19.55 21 19V9.5H3Z" fill="currentColor" />
    <path d="M3 8L4 4.5L8.5 5.5L7.5 8H3Z" fill="currentColor" />
    <path d="M9 8L10 4.5L14.5 5.5L13.5 8H9Z" fill="currentColor" />
    <path d="M15 8L16 4.5L20.5 5.5L19.5 8H15Z" fill="currentColor" />
  </svg>
</template>
