<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Collar and shirt outline -->
    <path d="M9 3C9 4.65685 10.3431 6 12 6C13.6569 6 15 4.65685 15 3L20 5.5L18 9.5L15.5 8.5V20.5C15.5 21.0523 15.0523 21.5 14.5 21.5H9.5C8.94772 21.5 8.5 21.0523 8.5 20.5V8.5L6 9.5L4 5.5L9 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Buttons -->
    <circle cx="12" cy="10" r="0.8" fill="currentColor" opacity="0.8" />
    <circle cx="12" cy="13.5" r="0.8" fill="currentColor" opacity="0.8" />
    <circle cx="12" cy="17" r="0.8" fill="currentColor" opacity="0.8" />
  </svg>
</template>
