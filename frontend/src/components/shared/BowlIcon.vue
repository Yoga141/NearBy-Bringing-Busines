<script setup lang="ts">
withDefaults(defineProps<{ size?: string }>(), { size: '1em' })
</script>

<template>
  <svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="inline-block flex-none align-[-0.15em]">
    <!-- Bowl body -->
    <path d="M3 11C3 16.5 6.8 20 12 20C17.2 20 21 16.5 21 11H3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
    <!-- Rim line -->
    <path d="M2.5 11H21.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    <!-- Bowl foot / stand -->
    <path d="M8.5 20V21.5H15.5V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    <!-- Steam swirls -->
    <path d="M7.5 7C7.5 7 8.5 5.5 8 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
    <path d="M12 7.5C12 7.5 13 6 12.5 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
    <path d="M16.5 7C16.5 7 17.5 5.5 17 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" />
    <!-- Noodles/soup surface line inside -->
    <path d="M6 13.5C8 14.5 10.5 13.5 13 14C15.5 14.5 17 13.8 18 13.5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" opacity="0.6" />
  </svg>
</template>
