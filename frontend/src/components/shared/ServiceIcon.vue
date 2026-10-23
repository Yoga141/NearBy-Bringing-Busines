<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Wrench head and handle -->
    <path d="M14.7 6.3C15.5 5.5 16.7 5.1 17.8 5.4C18.2 4.5 19 3.8 20 3.5C20.7 4.5 20.6 5.8 19.8 6.7C20.2 7.7 19.9 8.9 19 9.8L12.5 16.3C11.9 16.9 11 16.9 10.4 16.3L7.7 13.6C7.1 13 7.1 12.1 7.7 11.5L14.7 6.3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Tool shaft & grip -->
    <path d="M4 20L9.5 14.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    <path d="M18 18L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    <path d="M16 16L19 19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity="0.6" />
  </svg>
</template>
