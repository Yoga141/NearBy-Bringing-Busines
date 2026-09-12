<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useDashboardStore } from '@/stores/dashboard'
import { starsLabel } from '@/data/reviews'

const auth = useAuthStore()
const ui = useUiStore()
const dashboard = useDashboardStore()

// Data is loaded once by DashboardView (the parent) when the panel opens,
// shared across every tab via the store — no per-tab fetch needed here.

function addUmkm() {
  ui.openModal('editUmkm', { isNew: true })
}

function manage(id: number) {
  const full = dashboard.myUmkm.find((r) => r.id === id)
  ui.openModal('editUmkm', full ?? { isNew: true })
}
</script>

<template>
  <div class="mb-[22px] flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Halo, {{ auth.authFirst }} 👋</h1>
      <p class="mt-1.5 text-text-muted">Berikut ringkasan performa UMKM-mu.</p>
    </div>
    <button type="button" class="rounded-xl bg-brand-blue px-[22px] py-[13px] font-bold text-white shadow-[0_8px_20px_rgba(44,94,173,.25)]" @click="addUmkm">
      + Tambah UMKM
    </button>
  </div>

  <div v-if="dashboard.ownerLoading && !dashboard.myUmkm.length" class="rounded-2xl border border-border-card bg-white px-6 py-16 text-center text-text-muted">
    Memuat ringkasan…
  </div>

  <template v-else>
    <div class="mb-[22px] grid grid-cols-2 gap-4 tablet:grid-cols-4">
      <div v-for="s in dashboard.ownerStats" :key="s.label" class="rounded-2xl border border-border-card bg-white p-5">
        <div class="flex h-[38px] w-[38px] items-center justify-center rounded-[11px] text-[17px] font-extrabold" :style="{ background: s.soft, color: s.accent }">
          {{ s.icon }}
        </div>
        <div class="mt-3.5 text-[28px] font-extrabold tracking-[-.02em]">{{ s.value }}</div>
        <div class="text-[13px] font-semibold text-text-faint">{{ s.label }}</div>
      </div>
    </div>

    <div class="mb-[22px] grid grid-cols-1 gap-5 tablet:grid-cols-[1.5fr_1fr]">
      <div class="rounded-[18px] border border-border-card bg-white p-[22px]">
        <div class="flex items-center justify-between">
          <div class="text-[17px] font-extrabold">Kunjungan per UMKM</div>
        </div>
        <div v-if="dashboard.ownerViewsBars.length" class="mt-[22px] flex h-[180px] items-end gap-3.5">
          <div v-for="b in dashboard.ownerViewsBars" :key="b.label" class="flex h-full flex-1 flex-col items-center justify-end gap-2">
            <div class="text-[11px] font-bold text-text-faint">{{ b.value }}</div>
            <div class="w-full rounded-t-lg" style="background: linear-gradient(180deg, #4bb8fa, #2c5ead)" :style="{ height: `${b.pct}%` }" />
            <div class="max-w-full truncate text-[11.5px] font-bold text-text-faint" :title="b.label">{{ b.label }}</div>
          </div>
        </div>
        <p v-else class="mt-6 text-center text-[13.5px] text-text-faint">Belum ada UMKM untuk ditampilkan.</p>
      </div>
      <div class="rounded-[18px] border border-border-card bg-white p-[22px]">
        <div class="mb-3.5 text-[17px] font-extrabold">Ulasan terbaru</div>
        <div v-if="!dashboard.ownerRecentReviews.length" class="text-[13.5px] text-text-faint">Belum ada ulasan.</div>
        <div v-for="r in dashboard.ownerRecentReviews" :key="r.id" class="flex gap-2.5 border-t border-border-divider-2 py-3 first:border-t-0">
          <div class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-[#F7EDDC] text-[13px] font-extrabold text-gold">{{ r.initial }}</div>
          <div>
            <div class="flex items-center gap-2">
              <div class="text-sm font-bold">{{ r.name }}</div>
              <div class="text-xs font-bold text-gold">{{ starsLabel(r.stars) }}</div>
            </div>
            <div class="mt-0.5 text-[13px] leading-snug text-[#5B6470]">{{ r.text }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="rounded-[18px] border border-border-card bg-white p-[22px]">
      <div class="mb-4 text-[17px] font-extrabold">UMKM Saya</div>
      <div v-if="!dashboard.myUmkm.length" class="text-[13.5px] text-text-faint">Kamu belum mendaftarkan UMKM.</div>
      <div class="flex flex-col gap-3">
        <div v-for="u in dashboard.myUmkm" :key="u.id" class="flex flex-wrap items-center gap-[15px] rounded-2xl border border-border-divider p-3.5">
          <div class="h-[58px] w-[58px] flex-none rounded-xl" style="background: repeating-linear-gradient(135deg, #ece6da 0 9px, #f4efe6 9px 18px)" />
          <div class="flex-1">
            <div class="text-[15.5px] font-extrabold">{{ u.name }}</div>
            <div class="text-[13px] font-semibold text-text-faint">{{ u.cat }} · {{ u.loc }}</div>
          </div>
          <div class="text-center">
            <div class="font-extrabold text-gold">★ {{ u.rating }}</div>
            <div class="text-[11.5px] font-semibold text-text-faint">{{ u.reviews }} ulasan</div>
          </div>
          <div class="text-center">
            <div class="font-extrabold">{{ u.views }}</div>
            <div class="text-[11.5px] font-semibold text-text-faint">kunjungan</div>
          </div>
          <span class="rounded-full px-3 py-1.5 text-xs font-bold" :style="{ background: u.statusBg, color: u.statusColor }">{{ u.status }}</span>
          <button type="button" class="rounded-[10px] border border-[#E7E0D2] px-3.5 py-2 font-bold text-brand-navy" @click="manage(u.id)">
            Kelola
          </button>
        </div>
      </div>
    </div>
  </template>
</template>
