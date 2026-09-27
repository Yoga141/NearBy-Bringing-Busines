<script setup lang="ts">
import { computed, ref } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { useUiStore } from '@/stores/ui'
import ExcelCard from '@/components/dashboard/shared/ExcelCard.vue'
import UmkmThumb from '@/components/shared/UmkmThumb.vue'
import StarIcon from '@/components/shared/StarIcon.vue'
import PlusIcon from '@/components/shared/PlusIcon.vue'

const dashboard = useDashboardStore()
const ui = useUiStore()

const search = ref('')
const rows = computed(() => {
  const q = search.value.trim().toLowerCase()
  if (!q) return dashboard.allUmkmAdmin
  return dashboard.allUmkmAdmin.filter((u) =>
    `${u.name} ${u.cat} ${u.loc} ${u.ownerName ?? ''} ${u.ownerEmail ?? ''}`.toLowerCase().includes(q),
  )
})

function addUmkm() {
  ui.openModal('editUmkm', { isNew: true })
}

function edit(id: number) {
  // The modal needs the raw row (real status, items, photos), not the display row.
  const raw = dashboard.allUmkmAdminRaw.find((u) => u.id === id)
  if (raw) ui.openModal('editUmkm', raw)
}
</script>

<template>
  <div class="mb-[22px] flex flex-wrap items-center justify-between gap-4">
    <div>
      <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Semua UMKM</h1>
      <p class="mt-[7px] text-text-muted">Tambah, ubah, sembunyikan, atau hapus UMKM mana pun. UMKM yang ditambah admin langsung tampil.</p>
    </div>
    <button type="button" class="flex items-center gap-1.5 rounded-xl bg-brand-blue px-[22px] py-[13px] font-bold text-white" @click="addUmkm">
      <PlusIcon size="15px" /> Tambah UMKM
    </button>
  </div>

  <ExcelCard :is-admin="true" dataset="umkm" @imported="dashboard.fetchAdminDashboard()" />
  <ExcelCard :is-admin="true" dataset="produk" @imported="dashboard.fetchAdminDashboard()" />

  <input
    v-model="search"
    type="search"
    placeholder="Cari nama, kategori, wilayah, atau pemilik…"
    aria-label="Cari UMKM"
    class="mb-3.5 w-full max-w-[420px] rounded-xl border border-border-input bg-white px-3.5 py-2.5"
  />

  <div v-if="dashboard.adminLoading && !dashboard.allUmkmAdmin.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center text-text-muted">
    Memuat data UMKM…
  </div>
  <div v-else-if="!rows.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center text-text-muted">
    {{ search ? 'Tidak ada UMKM yang cocok.' : 'Belum ada UMKM.' }}
  </div>

  <div v-else class="overflow-x-auto rounded-[18px] border border-border-card bg-white px-5 pt-2 pb-3.5">
    <div class="grid min-w-[860px] grid-cols-[2.2fr_1fr_1fr_.7fr_.7fr_1.6fr] gap-3 border-b border-border-divider px-1.5 py-3.5 text-xs font-extrabold tracking-[.04em] text-[#B0A990] uppercase">
      <div>Nama UMKM</div>
      <div>Kategori</div>
      <div>Wilayah</div>
      <div>Rating</div>
      <div>Dilihat</div>
      <div class="text-right">Aksi</div>
    </div>
    <div
      v-for="u in rows"
      :key="u.id"
      class="grid min-w-[860px] grid-cols-[2.2fr_1fr_1fr_.7fr_.7fr_1.6fr] items-center gap-3 border-b border-[#F4EFE4] px-1.5 py-3 last:border-b-0"
    >
      <div class="flex min-w-0 items-center gap-3">
        <div class="h-[42px] w-[42px] flex-none overflow-hidden rounded-[10px]">
          <UmkmThumb :src="u.coverUrl" :alt="`Foto ${u.name}`" rounded="rounded-[10px]" label=" " />
        </div>
        <div class="min-w-0">
          <RouterLink :to="{ name: 'detail', params: { id: u.id } }" class="block overflow-hidden text-[14.5px] font-extrabold text-ellipsis whitespace-nowrap hover:underline">
            {{ u.name }}
          </RouterLink>
          <div class="flex flex-wrap items-center gap-1.5">
            <span class="rounded-md px-2 py-0.5 text-[11.5px] font-bold" :style="{ color: u.statusColor, background: u.statusBg }">{{ u.status }}</span>
            <span class="truncate text-[11.5px] text-text-faint">{{ u.ownerName ? `Pemilik: ${u.ownerName}` : 'Tanpa akun pemilik' }}</span>
          </div>
        </div>
      </div>
      <div><span class="rounded-full px-2.5 py-1 text-xs font-bold" :style="{ background: u.soft, color: u.accent }">{{ u.cat }}</span></div>
      <div class="text-[13.5px] font-semibold text-text-secondary">{{ u.loc }}</div>
      <div class="flex items-center gap-1 text-sm font-extrabold text-gold">
        <template v-if="u.reviews"><StarIcon size="12px" /> {{ u.rating }} <span class="text-xs font-semibold text-text-faint">({{ u.reviews }})</span></template>
        <span v-else class="text-xs font-semibold text-text-faint">Belum ada</span>
      </div>
      <div class="text-[13.5px] font-semibold text-text-secondary">{{ u.views.toLocaleString('id-ID') }}</div>
      <div class="flex justify-end gap-1.5">
        <button type="button" class="rounded-[9px] bg-brand-blue-tint px-3 py-2 text-[13px] font-bold text-brand-blue" @click="edit(u.id)">Edit</button>
        <button
          type="button"
          class="rounded-[9px] border px-3 py-2 text-[13px] font-bold whitespace-nowrap"
          :class="u.hidden ? 'border-[#8FC3B8] text-teal-deep hover:bg-teal-tint' : 'border-[#E7C97F] text-[#B07A1E] hover:bg-[#FBF3E4]'"
          @click="dashboard.adminToggleHidden(u.id, u.name)"
        >
          {{ u.hidden ? 'Tampilkan' : 'Sembunyikan' }}
        </button>
        <button type="button" class="rounded-[9px] border border-danger-border px-3 py-2 text-[13px] font-bold text-danger" @click="dashboard.adminDeleteUmkm(u.id, u.name)">
          Hapus
        </button>
      </div>
    </div>
  </div>

  <template v-if="dashboard.adminTrashUmkmRaw.length">
    <h2 class="mt-8 mb-3 text-[20px] font-extrabold">Tempat sampah UMKM ({{ dashboard.adminTrashUmkmRaw.length }})</h2>
    <div class="overflow-x-auto rounded-[18px] border border-border-card bg-white px-5 py-2">
      <div
        v-for="t in dashboard.adminTrashUmkmRaw"
        :key="t.id"
        class="flex min-w-[560px] items-center gap-3 border-b border-[#F4EFE4] py-3 last:border-b-0"
      >
        <div class="min-w-0 flex-1">
          <div class="truncate text-[14.5px] font-extrabold">{{ t.name }}</div>
          <div class="text-[12.5px] text-text-faint">{{ t.cat }} · {{ t.loc }} · dihapus {{ t.deletedAt }}</div>
        </div>
        <button type="button" class="rounded-[9px] bg-brand-blue-tint px-[13px] py-2 text-[13px] font-bold text-brand-blue" @click="dashboard.adminRestoreUmkm(t.id)">
          Pulihkan
        </button>
        <button type="button" class="rounded-[9px] border border-danger-border px-[13px] py-2 text-[13px] font-bold text-danger" @click="dashboard.adminPurgeUmkm(t.id, t.name)">
          Hapus permanen
        </button>
      </div>
    </div>
  </template>
</template>
