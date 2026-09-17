<script setup lang="ts">
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useUiStore } from '@/stores/ui'
import { useDashboardStore } from '@/stores/dashboard'
import { STAT_ICONS } from '@/lib/statIcons'
import CheckIcon from '@/components/shared/CheckIcon.vue'
import CloseIcon from '@/components/shared/CloseIcon.vue'
import RefreshIcon from '@/components/shared/RefreshIcon.vue'
import DocumentIcon from '@/components/shared/DocumentIcon.vue'

const auth = useAuthStore()
const ui = useUiStore()
const dashboard = useDashboardStore()

// Data is loaded once by DashboardView (the parent) when the panel opens.

const stats = computed(() => [
  { icon: 'hourglass', value: String(dashboard.pendingSubmissions.length), label: 'Menunggu verifikasi', accent: '#C98A2E', soft: '#F7EDDC' },
  {
    icon: 'grid',
    value: String(dashboard.allUmkmAdmin.filter((u) => u.verification === 'disetujui').length),
    label: 'Total UMKM aktif',
    accent: '#2C5EAD',
    soft: '#E6EDF8',
  },
])
</script>

<template>
  <div class="mb-[22px]">
    <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Antrian Verifikasi UMKM</h1>
    <p class="mt-[7px] text-text-muted">Halo, {{ auth.authFirst }} — tinjau kelayakan usaha dan kelengkapan data sebelum ditampilkan ke publik.</p>
  </div>

  <div class="mb-[26px] grid grid-cols-2 gap-4 tablet:grid-cols-4">
    <div v-for="s in stats" :key="s.label" class="rounded-2xl border border-border-card bg-white p-[18px]">
      <div class="flex h-[38px] w-[38px] items-center justify-center rounded-[11px] text-[17px] font-extrabold" :style="{ background: s.soft, color: s.accent }">
        <component :is="STAT_ICONS[s.icon]" size="18px" />
      </div>
      <div class="mt-[13px] text-[27px] font-extrabold tracking-[-.02em]">{{ s.value }}</div>
      <div class="text-[13px] font-semibold text-text-faint">{{ s.label }}</div>
    </div>
  </div>

  <div class="mb-3.5 text-lg font-extrabold">Menunggu ditinjau</div>

  <div v-if="!dashboard.pendingSubmissions.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center text-text-muted">
    Tidak ada pengajuan UMKM yang menunggu verifikasi.
  </div>

  <div v-else class="flex flex-col gap-4">
    <div v-for="p in dashboard.pendingSubmissions" :key="p.id" class="rounded-[18px] border border-border-card bg-white p-5 shadow-[0_4px_16px_rgba(19,50,77,.04)]">
      <div class="flex items-start gap-[15px]">
        <div class="h-[66px] w-[66px] flex-none rounded-[13px]" style="background: repeating-linear-gradient(135deg, #ece6da 0 9px, #f4efe6 9px 18px)" />
        <div class="flex-1">
          <div class="flex flex-wrap items-center gap-2.5">
            <div class="text-[17px] font-extrabold">{{ p.name }}</div>
            <span class="rounded-full px-[11px] py-1 text-[11.5px] font-bold" :style="{ background: p.catSoft, color: p.catAccent }">{{ p.cat }}</span>
          </div>
          <div class="mt-1 text-[13px] font-semibold text-text-faint">Pemilik: {{ p.owner }} · {{ p.loc }} · Diajukan {{ p.date }}</div>
        </div>
        <span class="rounded-full px-[13px] py-1.5 text-xs font-bold whitespace-nowrap" :style="{ background: p.verdictBg, color: p.verdictColor }">
          {{ p.verdict }}
        </span>
      </div>

      <div class="my-4 flex items-center gap-3">
        <div class="h-2 flex-1 overflow-hidden rounded-full bg-[#F0EADD]">
          <div class="h-full rounded-full" :style="{ background: p.barColor, width: `${p.pct}%` }" />
        </div>
        <div class="text-[12.5px] font-extrabold whitespace-nowrap text-brand-navy">{{ p.okCount }}/{{ p.total }} data lengkap</div>
      </div>

      <div class="mb-4 grid grid-cols-1 gap-2.5 mobile:grid-cols-2 tablet:grid-cols-3">
        <div v-for="c in p.checks" :key="c.label" class="flex items-center gap-2 text-[13px] font-semibold text-text-secondary">
          <span class="flex h-[19px] w-[19px] flex-none items-center justify-center rounded-full text-[11px] font-extrabold" :style="{ background: c.bg, color: c.color }">
            <CheckIcon v-if="c.mark" size="11px" /><CloseIcon v-else size="11px" />
          </span>
          {{ c.label }}
        </div>
      </div>

      <div class="flex flex-wrap gap-2.5 border-t border-border-divider-2 pt-3.5">
        <button type="button" class="flex items-center gap-1.5 rounded-[11px] bg-teal px-5 py-2.5 font-bold text-white" @click="dashboard.approveSubmission(p.id, p.name)">
          <CheckIcon size="14px" /> Setujui &amp; tampilkan
        </button>
        <button type="button" class="flex items-center gap-1.5 rounded-[11px] border border-[#E7C97F] bg-white px-[18px] py-2.5 font-bold text-[#B07A1E]" @click="dashboard.requestFix(p.name)">
          <RefreshIcon size="14px" /> Minta perbaikan data
        </button>
        <button type="button" class="flex items-center gap-1.5 rounded-[11px] border border-danger-border bg-white px-[18px] py-2.5 font-bold text-danger" @click="dashboard.rejectSubmission(p.id, p.name)">
          <CloseIcon size="14px" /> Tolak
        </button>
        <button
          type="button"
          class="ml-auto flex items-center gap-1.5 rounded-[11px] border border-[#E7E0D2] px-[18px] py-2.5 font-bold text-text-muted hover:bg-[#F4F0E7] hover:text-brand-navy"
          @click="ui.openModal('submissionDetail', p)"
        >
          <DocumentIcon size="14px" /> Lihat berkas
        </button>
      </div>
    </div>
  </div>
</template>
