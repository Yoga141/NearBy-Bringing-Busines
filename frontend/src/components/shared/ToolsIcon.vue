<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path
      d="M14.7 6.3C15.6 5.4 16.9 5.1 18.1 5.5L15.5 8.1L15.9 10.1L17.9 10.5L20.5 7.9C20.9 9.1 20.6 10.4 19.7 11.3C18.6 12.4 17 12.7 15.6 12.1L7.5 20.2C6.8 20.9 5.7 20.9 5 20.2C4.3 19.5 4.3 18.4 5 17.7L13.1 9.6C12.5 8.2 12.8 6.6 13.9 5.5"
      fill="currentColor"
      fill-rule="evenodd"
    />
  </svg>
</template>
