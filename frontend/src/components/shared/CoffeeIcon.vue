<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Cup body -->
    <path d="M4 8H16V13.5C16 16.5 13.8 18.5 10 18.5C6.2 18.5 4 16.5 4 13.5V8Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Cup Handle -->
    <path d="M16 10H17.8C19 10 20 11 20 12.2C20 13.5 19 14.5 17.8 14.5H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    <!-- Saucer plate -->
    <path d="M2.5 21H17.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    <!-- Steam aroma curves -->
    <path d="M7 5C7 5 7.8 3.8 7.3 2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
    <path d="M10.5 5C10.5 5 11.3 3.5 10.8 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
    <path d="M14 5C14 5 14.8 3.8 14.3 2.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
  </svg>
</template>
