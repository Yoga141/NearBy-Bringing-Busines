<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path
      d="M6 3H18M6 21H18M6.5 3C6.5 7 9 9.5 12 11C15 9.5 17.5 7 17.5 3M6.5 21C6.5 17 9 14.5 12 13C15 14.5 17.5 17 17.5 21"
      fill="none"
      stroke="currentColor"
      stroke-width="1.6"
      stroke-linecap="round"
      stroke-linejoin="round"
    />
  </svg>
</template>
