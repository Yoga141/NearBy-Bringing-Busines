<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <rect x="3" y="3" width="8" height="8" rx="1.2" fill="currentColor" />
    <rect x="13" y="3" width="8" height="8" rx="1.2" fill="currentColor" />
    <rect x="3" y="13" width="8" height="8" rx="1.2" fill="currentColor" />
    <rect x="13" y="13" width="8" height="8" rx="1.2" fill="currentColor" />
  </svg>
</template>
