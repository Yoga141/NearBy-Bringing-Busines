<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Gift box body -->
    <rect x="3.5" y="9" width="17" height="12" rx="2" stroke="currentColor" stroke-width="1.6" />
    <path d="M2.5 9H21.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    <path d="M12 9V21" stroke="currentColor" stroke-width="1.4" opacity="0.6" />
    <!-- Ribbon loops -->
    <path d="M12 9C10.5 6 7.5 6 8.5 4C9.5 2 12 6.5 12 9Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
    <path d="M12 9C13.5 6 16.5 6 15.5 4C14.5 2 12 6.5 12 9Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round" />
  </svg>
</template>
