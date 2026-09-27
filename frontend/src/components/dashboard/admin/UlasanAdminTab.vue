<script setup lang="ts">
import { computed, ref } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { starsCount } from '@/data/reviews'
import StarIcon from '@/components/shared/StarIcon.vue'

const dashboard = useDashboardStore()

const search = ref('')
const rows = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return dashboard.adminReviewsRaw
  return dashboard.adminReviewsRaw.filter((r) => `${r.name} ${r.umkmName ?? ''} ${r.text}`.toLowerCase().includes(q))
})
</script>

<template>
  <div class="mb-[22px]">
    <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Ulasan</h1>
    <p class="mt-[7px] text-text-muted">
      Semua ulasan pengguna, terbaru dulu. Hapus ulasan yang melanggar aturan; rating UMKM langsung dihitung ulang.
    </p>
  </div>

  <input
    v-model="search"
    type="search"
    placeholder="Cari penulis, UMKM, atau isi ulasan…"
    aria-label="Cari ulasan"
    class="mb-3.5 w-full max-w-[420px] rounded-xl border border-border-input bg-white px-3.5 py-2.5"
  />

  <div v-if="!rows.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center text-text-muted">
    {{ search ? 'Tidak ada ulasan yang cocok.' : 'Belum ada ulasan.' }}
  </div>
  <div v-else class="flex flex-col gap-3">
    <div v-for="r in rows" :key="r.id" class="rounded-[18px] border border-border-card bg-white p-4">
      <div class="flex flex-wrap items-center gap-2.5">
        <div class="font-extrabold">{{ r.name }}</div>
        <div class="flex items-center gap-0.5 text-gold">
          <StarIcon v-for="n in 5" :key="n" :filled="n <= starsCount(r.stars)" size="13px" />
        </div>
        <div class="text-[12.5px] font-semibold text-text-faint">{{ r.date }}</div>
        <RouterLink
          v-if="r.umkmName"
          :to="{ name: 'detail', params: { id: r.umkmId } }"
          class="rounded-full bg-brand-blue-tint px-2.5 py-0.5 text-[12px] font-bold text-brand-blue"
        >
          {{ r.umkmName }}
        </RouterLink>
        <button
          type="button"
          class="ml-auto rounded-[9px] border border-danger-border px-3 py-1.5 text-[13px] font-bold text-danger"
          @click="dashboard.adminDeleteReview(r.id, r.name)"
        >
          Hapus
        </button>
      </div>
      <p class="mt-1.5 text-[14.5px] leading-[1.55] whitespace-pre-line text-text-secondary">{{ r.text }}</p>
      <p v-if="r.reply" class="mt-1.5 text-[13px] text-text-faint"><b>Balasan pemilik:</b> {{ r.reply }}</p>
    </div>
  </div>
</template>
