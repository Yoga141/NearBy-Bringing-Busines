<script setup lang="ts">
withDefaults(defineProps<{ size?: string; filled?: boolean }>(), { size: '1em', filled: true })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <path
      d="M12 2L14.9 8.63L22 9.27L16.6 14.14L18.2 21.27L12 17.5L5.8 21.27L7.4 14.14L2 9.27L9.1 8.63L12 2Z"
      :fill="filled ? 'currentColor' : 'none'"
      :stroke="filled ? 'none' : 'currentColor'"
      stroke-width="1.5"
      stroke-linejoin="round"
    />
  </svg>
</template>
