<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Cloche handle -->
    <circle cx="12" cy="4.5" r="1.5" stroke="currentColor" stroke-width="1.6" />
    <!-- Cloche Dome -->
    <path d="M4.5 16C4.5 10.2 8 6.5 12 6.5C16 6.5 19.5 10.2 19.5 16H4.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Plate tray rim -->
    <path d="M2.5 16.5H21.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    <!-- Plate underside rim -->
    <path d="M5 19.5H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.7" />
    <!-- Shine highlight curve -->
    <path d="M8 13C8.5 10.5 10 9 12 9" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" opacity="0.6" />
  </svg>
</template>
