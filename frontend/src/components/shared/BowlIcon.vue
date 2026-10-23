<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M3 11C3 16 6.5 19.5 12 19.5C17.5 19.5 21 16 21 11H3Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M8 19.5V21.5H16V19.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M7 7C7 7 8 5.5 8 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
    <path d="M12 7C12 7 12 5.5 13 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
    <path d="M17 7C17 7 16 5.5 16 3.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
  </svg>
</template>
