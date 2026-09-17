<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path d="M4 9L5 4H19L20 9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <path d="M4 9C4 10.38 5.12 11.5 6.5 11.5C7.88 11.5 9 10.38 9 9C9 10.38 10.12 11.5 11.5 11.5C12.88 11.5 14 10.38 14 9C14 10.38 15.12 11.5 16.5 11.5C17.88 11.5 19 10.38 19 9" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M5 11.5V19C5 19.55 5.45 20 6 20H18C18.55 20 19 19.55 19 19V11.5" fill="none" stroke="currentColor" stroke-width="1.6" />
    <path d="M9.5 20V15C9.5 14.45 9.95 14 10.5 14H13.5C14.05 14 14.5 14.45 14.5 15V20" fill="none" stroke="currentColor" stroke-width="1.6" />
  </svg>
</template>
