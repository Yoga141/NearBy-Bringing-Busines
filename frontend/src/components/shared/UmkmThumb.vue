<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { getFoodPlaceholder } from '@/lib/foodPlaceholders'

/**
 * A UMKM photo, or a high-quality "makanan dan minuman" placeholder image
 * when there is no cover photo (or when the image fails to load).
 */
const props = withDefaults(
  defineProps<{
    src?: string | null
    alt: string
    rounded?: string
    variant?: 'warm' | 'teal' | 'blue' | 'navy'
    label?: string
    cat?: string
    id?: number | string
    useFoodPlaceholder?: boolean
  }>(),
  {
    src: null,
    rounded: 'rounded-none',
    variant: 'warm',
    label: '',
    cat: '',
    id: undefined,
    useFoodPlaceholder: true,
  },
)

const failed = ref(false)

// Instantly treat dummy/mock URLs (like contoh.com) as failed so visitors don't wait for a DNS timeout
function isMockUrl(url?: string | null): boolean {
  if (!url) return false
  return url.includes('contoh.com') || url.includes('example.com')
}

watch(
  () => props.src,
  (val) => {
    failed.value = isMockUrl(val)
  },
  { immediate: true },
)

const foodPlaceholder = computed(() => getFoodPlaceholder(props.id, props.cat))
</script>

<template>
  <img
    v-if="src && !failed"
    :src="src"
    :alt="alt"
    loading="lazy"
    decoding="async"
    class="block h-full max-h-full w-full max-w-full object-cover object-center transition-transform duration-300 group-hover:scale-105"
    :class="rounded"
    @error="failed = true"
  />
  <img
    v-else-if="useFoodPlaceholder"
    :src="foodPlaceholder"
    :alt="alt || 'Placeholder UMKM Makanan & Minuman'"
    loading="lazy"
    decoding="async"
    class="block h-full max-h-full w-full max-w-full object-cover object-center transition-transform duration-300 group-hover:scale-105"
    :class="rounded"
  />
</template>
