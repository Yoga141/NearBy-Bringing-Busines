<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M7 8L8.6 19.2C8.8 20.3 9.7 21 10.8 21H13.2C14.3 21 15.2 20.3 15.4 19.2L17 8H7Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M6 8H18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    <path d="M14 8L16 3H19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M8 12C10 12.5 12 11.5 14 12C15 12.3 15.8 12.1 16.5 11.8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
  </svg>
</template>
