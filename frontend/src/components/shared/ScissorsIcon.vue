<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Scissors finger rings -->
    <circle cx="6" cy="6" r="3" stroke="currentColor" stroke-width="1.6" />
    <circle cx="6" cy="18" r="3" stroke="currentColor" stroke-width="1.6" />
    <!-- Crossing blades -->
    <path d="M8.5 8.5L20 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    <path d="M8.5 15.5L20 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
  </svg>
</template>
