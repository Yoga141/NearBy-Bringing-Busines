<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path
      d="M12 6C10.5 4.7 8.3 4 6 4C4.8 4 3.6 4.2 2.5 4.6V18.1C3.6 17.7 4.8 17.5 6 17.5C8.3 17.5 10.5 18.2 12 19.5C13.5 18.2 15.7 17.5 18 17.5C19.2 17.5 20.4 17.7 21.5 18.1V4.6C20.4 4.2 19.2 4 18 4C15.7 4 13.5 4.7 12 6Z"
      fill="currentColor"
    />
  </svg>
</template>
