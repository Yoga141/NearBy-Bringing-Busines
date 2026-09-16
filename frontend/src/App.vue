<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useA11yStore } from '@/stores/a11y'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import HelpWidget from '@/components/layout/HelpWidget.vue'
import AccessibilityWidget from '@/components/layout/AccessibilityWidget.vue'
import VirtualKeyboard from '@/components/layout/VirtualKeyboard.vue'
import VoiceAssistant from '@/components/layout/VoiceAssistant.vue'
import SettingsModal from '@/components/account/SettingsModal.vue'
import RestoreBanner from '@/components/account/RestoreBanner.vue'

const route = useRoute()
const ui = useUiStore()
const a11y = useA11yStore()
const showChrome = computed(() => route.meta.chrome !== false)
</script>

<template>
  <div class="min-h-screen bg-cream">
    <RestoreBanner />
    <AppHeader v-if="showChrome" />
    <div class="relative">
      <RouterView v-slot="{ Component, route }">
        <Transition name="page">
          <component :is="Component" :key="route.path" />
        </Transition>
      </RouterView>
    </div>
    <AppFooter v-if="showChrome" />
    <HelpWidget v-if="showChrome" />
    <SettingsModal v-if="ui.settingsOpen" />
    <!-- Always mounted, chrome or not: the login and dashboard screens are
         exactly where the on-screen keyboard and read-aloud are needed most. -->
    <AccessibilityWidget />
    <VirtualKeyboard v-if="a11y.virtualKeyboard" />
    <!-- Renders nothing visible: the voice assistant is ear-only, and it must
         keep listening across every route, chrome or not. -->
    <VoiceAssistant />
  </div>
</template>
