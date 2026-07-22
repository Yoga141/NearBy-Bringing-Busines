<script setup lang="ts">
import { onMounted } from 'vue'
import { useUmkmStore } from '@/stores/umkm'
import HeroSection from '@/components/home/HeroSection.vue'
import CategorySection from '@/components/home/CategorySection.vue'
import FeaturedSection from '@/components/home/FeaturedSection.vue'
import RegionSection from '@/components/home/RegionSection.vue'
import OwnerCtaSection from '@/components/home/OwnerCtaSection.vue'

const umkm = useUmkmStore()

onMounted(() => umkm.loadAll())
</script>

<template>
  <main>
    <HeroSection />
    <CategorySection />
    <FeaturedSection />
    <RegionSection />
    <OwnerCtaSection />
  </main>
</template>
