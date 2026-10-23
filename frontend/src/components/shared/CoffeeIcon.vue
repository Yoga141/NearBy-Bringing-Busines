<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M4 8H16V14C16 16.2 14.2 18 12 18H8C5.8 18 4 16.2 4 14V8Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M16 10H17.5C18.9 10 20 11.1 20 12.5C20 13.9 18.9 15 17.5 15H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    <path d="M3 21H17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    <path d="M7 4C7 4 7 5.5 8 5.5C9 5.5 9 4 10 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
    <path d="M11 4C11 4 11 5.5 12 5.5C13 5.5 13 4 14 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
  </svg>
</template>
