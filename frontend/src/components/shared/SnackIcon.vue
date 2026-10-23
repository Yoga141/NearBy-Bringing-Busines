<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M4 14C4 9 7.5 6 12 6C16.5 6 20 9 20 14C20 18 16.5 19 12 19C7.5 19 4 18 4 14Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M8 12C9 10 10.5 9 12 9C13.5 9 15 10 16 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
    <path d="M7 15H17" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
  </svg>
</template>
