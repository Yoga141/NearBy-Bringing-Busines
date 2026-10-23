<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Bed posts / legs -->
    <path d="M3 7V20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
    <path d="M21 11V20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
    <!-- Bed base frame -->
    <path d="M3 16H21" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
    <!-- Mattress & blanket -->
    <path d="M3 13C3 11.9 3.9 11 5 11H19C20.1 11 21 11.9 21 13V16H3V13Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
    <!-- Pillows -->
    <rect x="5.5" y="8" width="5" height="3" rx="1" stroke="currentColor" stroke-width="1.3" />
    <rect x="12" y="8" width="5" height="3" rx="1" stroke="currentColor" stroke-width="1.3" />
  </svg>
</template>
