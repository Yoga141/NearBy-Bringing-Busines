<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Croissant outer arc -->
    <path d="M3 13.5C3.5 9 7.5 5.5 12 5.5C16.5 5.5 20.5 9 21 13.5C21.2 15.5 20 17 18 16.5C16 16 14.5 14.5 12 14.5C9.5 14.5 8 16 6 16.5C4 17 2.8 15.5 3 13.5Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Pastry fold curves -->
    <path d="M8 8.5C8.8 10.2 9.2 12.2 9 14" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" opacity="0.7" />
    <path d="M16 8.5C15.2 10.2 14.8 12.2 15 14" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" opacity="0.7" />
    <path d="M12 5.5V14.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" opacity="0.6" />
    <!-- Crumb accents -->
    <circle cx="19" cy="7.5" r="0.7" fill="currentColor" opacity="0.5" />
    <circle cx="5" cy="7.5" r="0.7" fill="currentColor" opacity="0.5" />
  </svg>
</template>
