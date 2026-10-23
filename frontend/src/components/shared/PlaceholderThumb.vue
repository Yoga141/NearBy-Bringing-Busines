<script setup lang="ts">
import { computed, type Component } from 'vue'

const props = withDefaults(
  defineProps<{
    label?: string
    icon?: Component
    variant?: 'warm' | 'teal' | 'blue' | 'navy'
    rounded?: string
  }>(),
  { variant: 'warm', rounded: 'rounded-[16px]' },
)

const gradients: Record<string, [string, string]> = {
  warm: ['#F6F0E6', '#EAE1D1'],
  teal: ['#EDF6F3', '#DBEBE7'],
  blue: ['#EEF4FC', '#DBE6F6'],
  navy: ['#1C3757', '#254974'],
}

const textColors: Record<string, string> = {
  warm: '#8C7D64',
  teal: '#3E8E82',
  blue: '#2C5EAD',
  navy: '#DCE7F6',
}

const bgStyle = computed(() => {
  const [c1, c2] = gradients[props.variant]
  return {
    background: `linear-gradient(140deg, ${c1} 0%, ${c2} 100%)`,
    color: textColors[props.variant],
  }
})
</script>

<template>
  <div
    class="placeholder-thumb flex h-full w-full items-center justify-center gap-1.5 px-3 text-center text-[12px] font-semibold transition-all duration-200"
    :data-variant="variant"
    :class="rounded"
    :style="bgStyle"
  >
    <component :is="icon" v-if="icon" size="1.4em" />
    <span v-if="label" class="truncate">{{ label }}</span>
  </div>
</template>
