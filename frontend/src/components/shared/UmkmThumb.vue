<script setup lang="ts">
import { ref, watch } from 'vue'
import PlaceholderThumb from './PlaceholderThumb.vue'
import ImageIcon from './ImageIcon.vue'

/**
 * A UMKM photo, or the striped placeholder when there is none (or the image
 * fails to load - e.g. an external link from an Excel import that went dead).
 */
const props = withDefaults(
  defineProps<{
    src?: string | null
    alt: string
    rounded?: string
    variant?: 'warm' | 'teal' | 'blue' | 'navy'
    label?: string
  }>(),
  { src: null, rounded: 'rounded-none', variant: 'warm', label: '' },
)

const failed = ref(false)
watch(
  () => props.src,
  () => {
    failed.value = false
  },
)
</script>

<template>
  <img
    v-if="src && !failed"
    :src="src"
    :alt="alt"
    loading="lazy"
    decoding="async"
    class="h-full w-full object-cover"
    :class="rounded"
    @error="failed = true"
  />
  <PlaceholderThumb v-else :icon="ImageIcon" :label="label || 'belum ada foto'" :variant="variant" :rounded="rounded" />
</template>
