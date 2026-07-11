<script setup lang="ts">
import { computed } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'

const props = defineProps<{ variant: 'admin' | 'owner' }>()
const dashboard = useDashboardStore()

const isAdmin = computed(() => props.variant === 'admin')

const rows = computed(() =>
  isAdmin.value
    ? dashboard.trashList.map((t) => ({ ...t, icon: t.icon, iconBg: t.iconBg, iconColor: t.iconColor, kindLabel: t.kindLabel as string | undefined }))
    : dashboard.ownerTrash.map((t) => ({
        ...t,
        icon: '🏪',
        iconBg: '#E3EFED',
        iconColor: '#3E8E82',
        kindLabel: undefined as string | undefined,
      })),
)
const count = computed(() => rows.value.length)
const subtitle = computed(() =>
  isAdmin.value
    ? 'Data yang dihapus disimpan di sini. Pulihkan bila keliru, atau hapus permanen.'
    : 'UMKM yang kamu hapus disimpan di sini. Pulihkan bila keliru, atau hapus permanen.',
)
const emptyDesc = computed(() =>
  isAdmin.value
    ? 'Belum ada data yang dihapus. Akun atau UMKM yang kamu hapus akan muncul di sini dan bisa dipulihkan.'
    : 'Belum ada UMKM yang dihapus. UMKM yang kamu hapus akan muncul di sini dan bisa dipulihkan.',
)

function emptyAll() {
  if (isAdmin.value) dashboard.emptyTrash()
  else dashboard.emptyOwnerTrash()
}
function restore(tid: string) {
  if (isAdmin.value) dashboard.adminRestoreTrash(tid)
  else dashboard.ownerRestoreUmkm(tid)
}
function purge(tid: string, name: string) {
  if (isAdmin.value) dashboard.adminPurgeTrash(tid, name)
  else dashboard.ownerPurgeUmkm(tid, name)
}
</script>

<template>
  <div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Tempat Sampah</h1>
      <p class="mt-[7px] text-text-muted">{{ subtitle }}</p>
    </div>
    <button
      v-if="count"
      type="button"
      class="rounded-[11px] border border-danger-border bg-white px-[18px] py-2.5 text-[13.5px] font-bold whitespace-nowrap text-danger"
      @click="emptyAll"
    >
      Kosongkan sampah
    </button>
  </div>

  <div v-if="!count" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center">
    <div class="text-[42px]">🗑️</div>
    <div class="mt-3 text-[19px] font-extrabold text-brand-navy">Tempat sampah kosong</div>
    <p class="mx-auto mt-2 max-w-[420px] text-[14.5px] leading-relaxed text-text-muted">{{ emptyDesc }}</p>
  </div>

  <div v-else class="overflow-x-auto rounded-[18px] border border-border-card bg-white px-5 pt-2 pb-3.5">
    <div
      class="grid min-w-[560px] gap-3 border-b border-border-divider px-1.5 py-3.5 text-xs font-extrabold tracking-[.04em] text-[#B0A990] uppercase"
      :class="isAdmin ? 'grid-cols-[2.2fr_1fr_1fr_auto]' : 'grid-cols-[2.6fr_1fr_auto]'"
    >
      <div>{{ isAdmin ? 'Item' : 'UMKM' }}</div>
      <div v-if="isAdmin">Jenis</div>
      <div>Dihapus</div>
      <div class="text-right">Aksi</div>
    </div>
    <div
      v-for="t in rows"
      :key="t.tid"
      class="grid min-w-[560px] items-center gap-3 border-b border-[#F4EFE4] px-1.5 py-3 last:border-b-0"
      :class="isAdmin ? 'grid-cols-[2.2fr_1fr_1fr_auto]' : 'grid-cols-[2.6fr_1fr_auto]'"
    >
      <div class="flex min-w-0 items-center gap-3">
        <div
          class="flex h-10 w-10 flex-none items-center justify-center rounded-[10px] text-lg"
          :style="{ background: t.iconBg, color: t.iconColor }"
        >
          {{ t.icon }}
        </div>
        <div class="min-w-0">
          <div class="overflow-hidden text-[14.5px] font-extrabold text-ellipsis whitespace-nowrap">{{ t.name }}</div>
          <div class="overflow-hidden text-[12.5px] text-ellipsis whitespace-nowrap text-text-faint">{{ t.sub }}</div>
        </div>
      </div>
      <div v-if="isAdmin">
        <span class="rounded-full px-2.5 py-1 text-[11.5px] font-bold" :style="{ background: t.iconBg, color: t.iconColor }">
          {{ t.kindLabel }}
        </span>
      </div>
      <div class="text-[13.5px] font-semibold text-text-secondary">{{ t.when }}</div>
      <div class="flex justify-end gap-1.5">
        <button type="button" class="rounded-[9px] bg-brand-blue-tint px-[13px] py-2 text-[13px] font-bold text-brand-blue" @click="restore(t.tid)">
          Pulihkan
        </button>
        <button
          type="button"
          class="rounded-[9px] border border-danger-border px-[13px] py-2 text-[13px] font-bold text-danger"
          @click="purge(t.tid, t.name)"
        >
          Hapus permanen
        </button>
      </div>
    </div>
  </div>
</template>
