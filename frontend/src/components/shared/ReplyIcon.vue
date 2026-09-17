<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path
      d="M9 8V4.5L2.5 11L9 17.5V13.8C14 13.8 17.3 15.4 19.5 19C18.7 13.5 15.5 9.2 9 8Z"
      fill="currentColor"
    />
  </svg>
</template>
