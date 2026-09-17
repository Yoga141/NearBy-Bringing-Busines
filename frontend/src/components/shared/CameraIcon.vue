<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M4 8H7L8.5 5.5H15.5L17 8H20C20.55 8 21 8.45 21 9V18C21 18.55 20.55 19 20 19H4C3.45 19 3 18.55 3 18V9C3 8.45 3.45 8 4 8Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <circle cx="12" cy="13.5" r="3.5" fill="none" stroke="currentColor" stroke-width="1.6" />
  </svg>
</template>
