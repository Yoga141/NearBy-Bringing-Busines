<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M8 5H21C21.55 5 22 5.45 22 6V18C22 18.55 21.55 19 21 19H8L2 12L8 5Z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
    <path d="M11 9.5L16.5 15M16.5 9.5L11 15" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
  </svg>
</template>
