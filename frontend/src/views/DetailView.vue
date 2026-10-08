<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useUmkmStore, type UmkmDetail } from '@/stores/umkm'
import { useReviewsStore } from '@/stores/reviews'
import CategoryPill from '@/components/shared/CategoryPill.vue'
import StarRating from '@/components/shared/StarRating.vue'
import DetailGallery from '@/components/detail/DetailGallery.vue'
import ItemList from '@/components/detail/ItemList.vue'
import ReviewList from '@/components/detail/ReviewList.vue'
import ReviewForm from '@/components/detail/ReviewForm.vue'
import DetailSidebar from '@/components/detail/DetailSidebar.vue'
import Lightbox from '@/components/detail/Lightbox.vue'
import ArrowLeftIcon from '@/components/shared/ArrowLeftIcon.vue'
import DotIcon from '@/components/shared/DotIcon.vue'

const props = defineProps<{ id: string }>()

const umkm = useUmkmStore()
const reviewsStore = useReviewsStore()

const sel = ref<UmkmDetail | null>(null)
const notFound = ref(false)

async function load() {
  notFound.value = false
  const detail = await umkm.fetchDetail(Number(props.id))
  sel.value = detail
  notFound.value = !detail
  if (detail) reviewsStore.setReviews(detail.id, detail.reviewsList)
}

onMounted(load)
watch(() => props.id, load)

/** After a review changes, pull the recounted rating from the server. */
async function refreshRating() {
  if (!sel.value) return
  const fresh = await umkm.fetchDetail(sel.value.id)
  if (fresh) {
    sel.value = { ...sel.value, rating: fresh.rating, reviews: fresh.reviews }
    reviewsStore.setReviews(fresh.id, fresh.reviewsList)
  }
}

// Only what the owner actually wrote - no filler sentences.
const desc = computed(() => sel.value?.tag.trim() || '')

/** wa.me link from an Indonesian number ("0812-…", "+62 812…", "62812…"); empty when there is no number. */
const waLink = computed(() => {
  const digits = sel.value?.phone.replace(/[^0-9]/g, '') ?? ''
  if (digits.length < 8) return ''
  const intl = digits.startsWith('62') ? digits : digits.startsWith('0') ? `62${digits.slice(1)}` : `62${digits}`
  return `https://wa.me/${intl}`
})

const STATUS_META = {
  Aktif: { c: '#2E7D6E', b: '#E3EFED', pub: 'Buka' },
  Libur: { c: '#B07A1E', b: '#F7EDDC', pub: 'Sedang Libur' },
  Tutup: { c: '#C0472F', b: '#F8E6E0', pub: 'Tutup Sementara' },
} as const

const statusMeta = computed(() => STATUS_META[sel.value?.status ?? 'Aktif'])

const reviews = computed(() => (sel.value ? reviewsStore.reviewsFor(sel.value.id) : []))
</script>

<template>
  <main class="mx-auto max-w-[1120px] px-6 pt-6 pb-20">
    <RouterLink :to="{ name: 'daftar' }" class="mb-[18px] inline-flex items-center gap-1.5 font-bold text-text-muted">
      <ArrowLeftIcon size="14px" /> Kembali ke daftar
    </RouterLink>

    <div v-if="umkm.detailLoading && !sel" class="rounded-[20px] border border-border-card bg-white px-6 py-24 text-center text-text-muted">
      Memuat data UMKM…
    </div>

    <div v-else-if="notFound" class="rounded-[20px] border border-border-card bg-white px-6 py-24 text-center">
      <div class="text-xl font-extrabold text-brand-navy">UMKM tidak ditemukan</div>
      <p class="mx-auto mt-2 max-w-[420px] text-[15px] text-text-muted">
        {{ umkm.detailError || 'UMKM ini mungkin sudah tidak tersedia atau belum disetujui.' }}
      </p>
    </div>

    <template v-else-if="sel">
      <DetailGallery :name="sel.name" :photos="sel.photos" />

      <div class="grid grid-cols-1 items-start gap-[34px] tablet:grid-cols-[1.6fr_.9fr]">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2.5">
            <CategoryPill :category="sel.cat" />
            <span
              class="flex items-center gap-1 rounded-full px-[13px] py-1.5 text-[12.5px] font-extrabold"
              :style="{ background: statusMeta.b, color: statusMeta.c }"
            >
              <DotIcon size="8px" /> {{ statusMeta.pub }}
            </span>
            <span class="text-[13.5px] font-semibold text-text-faint">{{ sel.loc }}</span>
          </div>
          <h1 class="mt-3.5 mb-1.5 text-[28px] font-extrabold tracking-[-.02em] wrap-break-word tablet:text-[36px]">{{ sel.name }}</h1>
          <div class="mb-[22px] flex flex-wrap items-center gap-x-3.5 gap-y-2">
            <StarRating :rating="sel.rating" />
            <div class="font-semibold text-text-faint">{{ sel.reviews ? `${sel.reviews} ulasan` : 'Belum ada ulasan' }}</div>
            <template v-if="sel.priceLabel">
              <div class="h-[5px] w-[5px] rounded-full bg-[#D6CFC0]" />
              <div class="rounded-lg bg-[#F4F0E7] px-3 py-1 text-[13px] font-bold text-brand-navy">{{ sel.priceLabel }}</div>
            </template>
          </div>

          <template v-if="desc">
            <h3 class="mb-2 text-[19px] font-extrabold">Tentang tempat ini</h3>
            <p class="mb-[26px] text-[15.5px] leading-[1.7] whitespace-pre-line wrap-break-word text-text-secondary">{{ desc }}</p>
          </template>

          <template v-if="sel.items.length">
            <h3 class="mb-3.5 text-[19px] font-extrabold">{{ sel.listLabel || 'Produk & layanan' }}</h3>
            <ItemList :items="sel.items" />
          </template>

          <h3 class="mb-3.5 text-[19px] font-extrabold">Ulasan &amp; rating</h3>
          <p v-if="!reviews.length" class="py-3 text-[14.5px] text-text-muted">Belum ada ulasan. Jadilah yang pertama!</p>
          <ReviewList :reviews="reviews" @changed="refreshRating" />
          <ReviewForm :umkm-id="sel.id" :owner-id="sel.ownerId" @changed="refreshRating" />
        </div>

        <DetailSidebar :umkm="sel" :wa-link="waLink" />
      </div>

      <Lightbox />
    </template>
  </main>
</template>
