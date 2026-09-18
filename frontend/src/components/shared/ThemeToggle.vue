<script setup lang="ts">
import { computed } from 'vue'
import { useThemeStore } from '@/stores/theme'
import SunIcon from './SunIcon.vue'
import MoonIcon from './MoonIcon.vue'

const theme = useThemeStore()

/** Names the action, not the state: "switch to dark" while it is light. */
const label = computed(() => (theme.isDark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'))
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="theme.isDark"
    :aria-label="label"
    :title="label"
    class="flex h-[42px] w-[42px] flex-none items-center justify-center rounded-xl border border-border-input bg-white text-lg text-brand-navy transition-colors hover:bg-surface-alt"
    @click="theme.toggle"
  >
    <!-- Shows where a click takes you: the moon in light mode, the sun in dark. -->
    <SunIcon v-if="theme.isDark" size="19px" />
    <MoonIcon v-else size="18px" />
  </button>
</template>
