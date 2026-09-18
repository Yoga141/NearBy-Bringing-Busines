<script setup lang="ts">
import { useRouter } from 'vue-router'
import logo from '@/assets/logo-nearby.png'
import ThemeToggle from '@/components/shared/ThemeToggle.vue'

withDefaults(defineProps<{ brandGradient: string; footerColor?: string }>(), {
  footerColor: '#8FA6C6',
})
const router = useRouter()
</script>

<template>
  <div class="grid min-h-screen grid-cols-1 tablet:grid-cols-2">
    <div
      class="relative hidden flex-col justify-between overflow-hidden p-14 tablet:flex"
      :style="{ background: brandGradient }"
    >
      <slot name="brand-decor" />
      <div class="relative flex cursor-pointer items-center gap-[11px]" @click="router.push({ name: 'beranda' })">
        <img :src="logo" class="h-11 w-11 rounded-xl bg-white object-contain p-1" />
        <div class="text-[19px] font-extrabold text-white">Near<span class="text-gold-bright">By</span> Balikpapan</div>
      </div>
      <div class="relative">
        <slot name="brand-middle" />
      </div>
      <div class="relative text-[13px] font-semibold" :style="{ color: footerColor }">
        Yang terdekat, yang terbaik sekitaran Balikpapan.
      </div>
    </div>

    <!-- data-a11y-read: what "Bacakan isi layar" reads on this page, since the
         auth screens have no <main> and the decorative panel isn't worth reading. -->
    <div data-a11y-read class="relative flex items-center justify-center bg-cream px-6 py-10">
      <!-- Top-left: the accessibility button already owns the top-right corner. -->
      <ThemeToggle class="absolute top-5 left-5" />
      <slot name="form" />
    </div>
  </div>
</template>
