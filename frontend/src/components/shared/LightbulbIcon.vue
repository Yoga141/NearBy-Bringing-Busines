<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path
      d="M9 18H15M10 21H14M12 3C8.5 3 6 5.6 6 9C6 11.2 7.1 12.7 8.2 13.8C8.8 14.4 9 15 9 15.7V16H15V15.7C15 15 15.2 14.4 15.8 13.8C16.9 12.7 18 11.2 18 9C18 5.6 15.5 3 12 3Z"
      fill="none"
      stroke="currentColor"
      stroke-width="1.6"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
  </svg>
</template>
