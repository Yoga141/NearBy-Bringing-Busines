<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useUiStore } from '@/stores/ui'
import { useDashboardStore } from '@/stores/dashboard'

const ui = useUiStore()
const dashboard = useDashboardStore()
const router = useRouter()

function viewPublic() {
  const id = ui.modalItem?.id
  ui.closeModal()
  if (id != null) router.push({ name: 'detail', params: { id } })
}
function toggleHide() {
  const item = ui.modalItem
  ui.closeModal()
  if (item) dashboard.adminToggleHidden(item.id, item.name)
}
function deleteUmkm() {
  const item = ui.modalItem
  if (!item) return
  dashboard.adminDeleteUmkm(item.id, item.name, item.cat, item.loc)
  ui.closeModal()
}
</script>

<template>
  <div class="fixed inset-0 z-[90] flex items-center justify-center bg-[rgba(15,30,45,.5)] p-6" @click="ui.closeModal">
    <div class="w-full max-w-[288px] rounded-2xl bg-white p-2 shadow-[0_30px_70px_rgba(9,24,40,.4)]" @click.stop>
      <div class="mb-1 border-b border-[#F2ECDF] px-3 pt-2.5 pb-2 text-xs font-bold text-text-faint">{{ ui.modalItem?.name }}</div>
      <button type="button" class="block w-full rounded-[9px] px-3 py-2.5 text-left text-sm font-semibold text-brand-navy hover:bg-[#F4F0E7]" @click="viewPublic">
        Lihat halaman publik
      </button>
      <button type="button" class="block w-full rounded-[9px] px-3 py-2.5 text-left text-sm font-semibold text-brand-navy hover:bg-[#F4F0E7]" @click="toggleHide">
        Sembunyikan / Tampilkan
      </button>
      <button type="button" class="block w-full rounded-[9px] px-3 py-2.5 text-left text-sm font-bold text-danger hover:bg-[#F8E6E0]" @click="deleteUmkm">
        Hapus UMKM
      </button>
    </div>
  </div>
</template>
