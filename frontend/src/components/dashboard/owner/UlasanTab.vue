<script setup lang="ts">
import { useUiStore } from '@/stores/ui'
import { useDashboardStore } from '@/stores/dashboard'
import { starsCount } from '@/data/reviews'
import StarIcon from '@/components/shared/StarIcon.vue'
import EditIcon from '@/components/shared/EditIcon.vue'
import ReplyIcon from '@/components/shared/ReplyIcon.vue'

const ui = useUiStore()
const dashboard = useDashboardStore()

// Data is loaded once by DashboardView (the parent) when the panel opens.
</script>

<template>
  <div class="mb-[22px]">
    <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Ulasan</h1>
    <p class="mt-1.5 text-text-muted">Semua ulasan dari warga untuk usaha-usahamu.</p>
  </div>

  <div v-if="dashboard.ownerLoading && !dashboard.ownerReviewsRaw.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center text-text-muted">
    Memuat ulasan…
  </div>
  <div v-else-if="!dashboard.ownerReviewsRaw.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center text-text-muted">
    Belum ada ulasan untuk UMKM-mu.
  </div>
  <div v-else class="rounded-[18px] border border-border-card bg-white px-6 pt-2 pb-3">
    <div v-for="r in dashboard.ownerReviewsRaw" :key="r.id" class="flex gap-3.5 border-t border-border-divider-2 py-4.5 first:border-t-0">
      <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full bg-[#F7EDDC] text-[15px] font-extrabold text-gold">{{ r.initial }}</div>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2.5">
          <div class="text-[15px] font-extrabold">{{ r.name }}</div>
          <div class="flex items-center gap-0.5 text-gold">
            <StarIcon v-for="n in 5" :key="n" :filled="n <= starsCount(r.stars)" size="13px" />
          </div>
          <div class="text-[12.5px] font-semibold text-[#B0A990]">· {{ r.date }}</div>
        </div>
        <div class="mt-0.5 text-[12.5px] font-bold text-brand-blue">untuk {{ r.umkmName }}</div>
        <div class="mt-[7px] text-sm leading-relaxed text-[#5B6470]">{{ r.text }}</div>
        <div v-if="r.reply" class="mt-2.5 rounded-[10px] bg-[#F4F0E7] px-3.5 py-2.5 text-[13px] leading-relaxed text-brand-navy">
          <b>Balasanmu:</b> {{ r.reply }}
        </div>
        <button
          type="button"
          class="mt-[11px] flex items-center gap-1.5 rounded-[10px] border border-[#E7E0D2] px-4 py-2 text-[13px] font-bold text-brand-navy"
          @click="ui.openModal('replyReview', r)"
        >
          <EditIcon v-if="r.reply" size="13px" /><ReplyIcon v-else size="13px" />
          {{ r.reply ? 'Ubah balasan' : 'Balas' }}
        </button>
      </div>
    </div>
  </div>
</template>
