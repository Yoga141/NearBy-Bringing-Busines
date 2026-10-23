<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Cup body -->
    <path d="M6.5 8L8 20C8.2 21.1 9.1 21.8 10.2 21.8H13.8C14.9 21.8 15.8 21.1 16 20L17.5 8H6.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Cup lid rim -->
    <path d="M5.5 8H18.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    <!-- Cup dome -->
    <path d="M7.5 8C7.5 5.5 9.5 4 12 4C14.5 4 16.5 5.5 16.5 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
    <!-- Straw -->
    <path d="M12 4L14 1.5H17" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    <!-- Ice wave / liquid line -->
    <path d="M8 13.5C9.5 14.2 11.5 13.5 13.5 14C15 14.4 15.8 14 16 13.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" opacity="0.7" />
    <!-- Boba / ice drops -->
    <circle cx="10" cy="18" r="0.9" fill="currentColor" opacity="0.6" />
    <circle cx="14" cy="18" r="0.9" fill="currentColor" opacity="0.6" />
    <circle cx="12" cy="16.5" r="0.9" fill="currentColor" opacity="0.6" />
  </svg>
</template>
