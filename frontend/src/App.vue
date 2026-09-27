<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue'
import { useRoute } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useThemeStore } from '@/stores/theme'
import { FEATURES } from '@/config/features'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import HelpWidget from '@/components/layout/HelpWidget.vue'
import SettingsModal from '@/components/account/SettingsModal.vue'

// Switched-off features (see config/features.ts) are loaded lazily, so while
// their flag is off their code is never even downloaded - and their stores,
// microphone listeners and API calls never start.
const AssistantChat = defineAsyncComponent(() => import('@/components/layout/AssistantChat.vue'))
const AccessibilityLayer = defineAsyncComponent(() => import('@/components/layout/AccessibilityLayer.vue'))

const route = useRoute()
const ui = useUiStore()
// Instantiated here so the theme follows OS changes on every route, including
// the ones (login, dashboard) that don't render the header with the toggle.
useThemeStore()
const showChrome = computed(() => route.meta.chrome !== false)
</script>

<template>
  <div class="min-h-screen bg-cream">
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
    <AssistantChat v-if="FEATURES.assistant && showChrome" />
    <SettingsModal v-if="ui.settingsOpen" />
    <AccessibilityLayer v-if="FEATURES.accessibility" />
  </div>
</template>
