<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Woven bag / basket body -->
    <path d="M4 10C4 9.44772 4.44772 9 5 9H19C19.5523 9 20 9.44772 20 10L18.8 20.2C18.7 20.7 18.2 21 17.7 21H6.3C5.8 21 5.3 20.7 5.2 20.2L4 10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Handle loop -->
    <path d="M8.5 9V6C8.5 4.34315 9.84315 3 11.5 3H12.5C14.1569 3 15.5 4.34315 15.5 6V9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
    <!-- Woven lattice structure -->
    <path d="M4.5 13H19.5" stroke="currentColor" stroke-width="1.3" opacity="0.6" stroke-linecap="round" />
    <path d="M5 17H19" stroke="currentColor" stroke-width="1.3" opacity="0.6" stroke-linecap="round" />
    <path d="M9.5 9V21" stroke="currentColor" stroke-width="1.3" opacity="0.6" />
    <path d="M14.5 9V21" stroke="currentColor" stroke-width="1.3" opacity="0.6" />
  </svg>
</template>
