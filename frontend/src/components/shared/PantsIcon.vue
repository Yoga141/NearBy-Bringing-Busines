<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Pants outline -->
    <path d="M5.5 4H18.5L17.5 20C17.5 20.5523 17.0523 21 16.5 21H13C12.4477 21 12 20.5523 12 20V12V20C12 20.5523 11.5523 21 11 21H7.5C6.94772 21 6.5 20.5523 6.5 20L5.5 4Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Waistband stitch line -->
    <path d="M5.5 7H18.5" stroke="currentColor" stroke-width="1.3" opacity="0.6" stroke-linecap="round" />
    <!-- Pocket lines -->
    <path d="M6 9.5L8.5 11.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity="0.6" />
    <path d="M18 9.5L15.5 11.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity="0.6" />
  </svg>
</template>
