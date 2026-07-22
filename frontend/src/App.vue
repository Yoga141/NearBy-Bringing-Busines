<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useAuthStore } from '@/stores/auth'
import { useUmkmStore } from '@/stores/umkm'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppFooter from '@/components/layout/AppFooter.vue'
import HelpWidget from '@/components/layout/HelpWidget.vue'
import SettingsModal from '@/components/account/SettingsModal.vue'
import RestoreBanner from '@/components/account/RestoreBanner.vue'

const route = useRoute()
const ui = useUiStore()
const auth = useAuthStore()
const umkm = useUmkmStore()
const showChrome = computed(() => route.meta.chrome !== false)

// Validasi ulang token tersimpan supaya sesi bertahan setelah refresh.
onMounted(async () => {
  await auth.restoreSession()
  if (auth.isAuthed) umkm.loadFavorites()
})

// Muat favorit saat login, kosongkan saat logout — supaya ikon hati konsisten.
watch(
  () => auth.isAuthed,
  (authed) => {
    if (authed) umkm.loadFavorites()
    else umkm.clearFavorites()
  },
)
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
  </div>
</template>
