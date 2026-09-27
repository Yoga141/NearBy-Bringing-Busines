<script setup lang="ts">
import { useA11yStore } from '@/stores/a11y'
import AccessibilityWidget from './AccessibilityWidget.vue'
import VirtualKeyboard from './VirtualKeyboard.vue'
import VoiceAssistant from './VoiceAssistant.vue'

/**
 * Everything of the accessibility feature in one place, mounted by App.vue
 * only while FEATURES.accessibility is on.
 */
const a11y = useA11yStore()
</script>

<template>
  <!-- Always mounted, chrome or not: the login and dashboard screens are
       exactly where the on-screen keyboard and read-aloud are needed most. -->
  <AccessibilityWidget />
  <VirtualKeyboard v-if="a11y.virtualKeyboard" />
  <!-- Renders nothing visible: the voice assistant is ear-only, and it must
       keep listening across every route, chrome or not. -->
  <VoiceAssistant />
</template>
