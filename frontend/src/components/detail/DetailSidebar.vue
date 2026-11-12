<script setup lang="ts">
import { computed } from 'vue'
import FavoriteButton from '@/components/shared/FavoriteButton.vue'
import ExternalLinkIcon from '@/components/shared/ExternalLinkIcon.vue'
import type { Umkm } from '@/types'
import { googleMapsPinUrl, googleMapsSearchUrl, isWithinBalikpapan, osmEmbedUrl, type LatLng } from '@/lib/geo'

const props = defineProps<{ umkm: Umkm; waLink: string }>()

// With a saved pin the map and link open exactly on it. Without one we can
// only search the address text - scoped to the kecamatan and province, so a
// vague address ("RT 34") isn't matched to a namesake in another city.
const pin = computed<LatLng | null>(() => {
  const { lat, lng } = props.umkm
  if (lat == null || lng == null) return null
  const point = { lat, lng }
  // A stray pin from before validation existed would show the wrong place - treat it as unset.
  return isWithinBalikpapan(point) ? point : null
})
const mapsLink = computed(() => {
  if (pin.value) return googleMapsPinUrl(pin.value)
  return props.umkm.address ? googleMapsSearchUrl(props.umkm.address, props.umkm.loc) : ''
})
const igHandle = computed(() => props.umkm.ig.trim().replace(/^@/, '').replace(/^https?:\/\/(www\.)?instagram\.com\//i, '').replace(/\/$/, ''))
</script>

<template>
  <aside class="min-w-0 rounded tablet:sticky tablet:top-[90px]-[18px] border border-border-card bg-white p-[22px] shadow-[0_8px_26px_rgba(19,50,77,.06)]">
    <div class="mb-1 font-extrabold">Informasi kontak</div>

    <div class="border-b border-[#F2ECDF] py-[11px]">
      <div class="text-xs font-bold text-text-faint uppercase">Alamat</div>
      <div class="text-[14px] wrap-break-word text-brand-navy">{{ umkm.address || 'Belum diisi pemilik' }}</div>
      <div v-if="pin" class="mt-2 overflow-hidden rounded-xl border border-border-card">
        <iframe
          :src="osmEmbedUrl(pin)"
          :title="`Peta lokasi ${umkm.name}`"
          class="block h-[170px] w-full border-0"
          loading="lazy"
          referrerpolicy="no-referrer"
        />
      </div>
      <a
        v-if="mapsLink"
        :href="mapsLink"
        target="_blank"
        rel="noopener"
        class="mt-1 inline-flex items-center gap-1 text-[13px] font-bold text-brand-blue"
      >
        {{ pin ? 'Buka di Google Maps' : 'Cari alamat di Google Maps' }} <ExternalLinkIcon size="12px" />
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
        class="text-[14px] font-semibold break-all text-brand-blue"
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
