<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path
      d="M9 11V5.5C9 4.67 9.67 4 10.5 4C11.33 4 12 4.67 12 5.5V10M12 10V4.5C12 3.67 12.67 3 13.5 3C14.33 3 15 3.67 15 4.5V10M15 10V5.5C15 4.67 15.67 4 16.5 4C17.33 4 18 4.67 18 5.5V13C18 17.42 14.42 21 10 21C7.5 21 5.5 19.5 4 17L3 15.3"
      fill="none"
      stroke="currentColor"
      stroke-width="1.5"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
    <path d="M9 11V8.5C9 7.67 8.33 7 7.5 7C6.67 7 6 7.67 6 8.5V13" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
  </svg>
</template>
