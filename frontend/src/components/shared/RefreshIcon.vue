<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path
      d="M4 12C4 7.58 7.58 4 12 4C14.5 4 16.71 5.15 18.17 6.95M20 12C20 16.42 16.42 20 12 20C9.5 20 7.29 18.85 5.83 17.05M18.5 3.5V7.5H14.5M9.5 20.5V16.5H5.5"
      fill="none"
      stroke="currentColor"
      stroke-width="1.8"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
  </svg>
</template>
