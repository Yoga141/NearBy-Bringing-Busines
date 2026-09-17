<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M6 2H14L19 7V21C19 21.55 18.55 22 18 22H6C5.45 22 5 21.55 5 21V3C5 2.45 5.45 2 6 2Z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <path d="M14 2V7H19" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
  </svg>
</template>
