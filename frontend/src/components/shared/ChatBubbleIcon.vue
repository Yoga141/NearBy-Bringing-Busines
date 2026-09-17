<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M4 4H20C20.55 4 21 4.45 21 5V15C21 15.55 20.55 16 20 16H8L4 20V16C3.45 16 3 15.55 3 15V5C3 4.45 3.45 4 4 4Z" fill="currentColor" />
  </svg>
</template>
