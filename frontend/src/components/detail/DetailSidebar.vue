<script setup lang="ts">
import { computed } from 'vue'
import FavoriteButton from '@/components/shared/FavoriteButton.vue'
import ExternalLinkIcon from '@/components/shared/ExternalLinkIcon.vue'
import type { Umkm } from '@/types'

const props = defineProps<{ umkm: Umkm; waLink: string }>()

// A real search link for the address - the UMKM table stores no coordinates,
// so there is no map to embed.
const mapsLink = computed(() =>
  props.umkm.address
    ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${props.umkm.address}, Balikpapan`)}`
    : '',
)
const igHandle = computed(() => props.umkm.ig.trim().replace(/^@/, '').replace(/^https?:\/\/(www\.)?instagram\.com\//i, '').replace(/\/$/, ''))
</script>

<template>
  <aside class="sticky top-[90px] rounded-[18px] border border-border-card bg-white p-[22px] shadow-[0_8px_26px_rgba(19,50,77,.06)]">
    <div class="mb-1 font-extrabold">Informasi kontak</div>

    <div class="border-b border-[#F2ECDF] py-[11px]">
      <div class="text-xs font-bold text-text-faint uppercase">Alamat</div>
      <div class="text-[14px] text-brand-navy">{{ umkm.address || 'Belum diisi pemilik' }}</div>
      <a
        v-if="mapsLink"
        :href="mapsLink"
        target="_blank"
        rel="noopener"
        class="mt-1 inline-flex items-center gap-1 text-[13px] font-bold text-brand-blue"
      >
        Buka di Google Maps <ExternalLinkIcon size="12px" />
      </a>
    </div>
    <div class="border-b border-[#F2ECDF] py-[11px]">
      <div class="text-xs font-bold text-text-faint uppercase">Jam buka</div>
      <div class="text-[14px] text-brand-navy">{{ umkm.hours || 'Belum diisi pemilik' }}</div>
    </div>
    <div class="border-b border-[#F2ECDF] py-[11px]">
      <div class="text-xs font-bold text-text-faint uppercase">Telepon</div>
      <div class="text-[14px] text-brand-navy">{{ umkm.phone || 'Belum diisi pemilik' }}</div>
    </div>
    <div class="py-[11px]">
      <div class="text-xs font-bold text-text-faint uppercase">Sosial Media</div>
      <a
        v-if="igHandle"
        :href="`https://instagram.com/${igHandle}`"
        target="_blank"
        rel="noopener"
        class="text-[14px] font-semibold text-brand-blue"
      >
        @{{ igHandle }}
      </a>
      <div v-else class="text-[14px] text-brand-navy">Belum diisi pemilik</div>
    </div>

    <a
      v-if="waLink"
      :href="waLink"
      target="_blank"
      rel="noopener"
      class="mb-2.5 mt-3.5 flex w-full items-center justify-center gap-2 rounded-xl bg-teal py-3 font-bold text-white"
    >
      Hubungi via WhatsApp
    </a>
    <FavoriteButton :id="umkm.id" variant="button" />
  </aside>
</template>
