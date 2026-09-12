<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useUmkmStore } from '@/stores/umkm'
import { useDashboardStore } from '@/stores/dashboard'
import { CATEGORY_NAMES, LOCATION_NAMES } from '@/data/categories'
import type { CategoryName, LocationName } from '@/types'
import BaseModal from '@/components/shared/BaseModal.vue'

const ui = useUiStore()
const umkmStore = useUmkmStore()
const dashboard = useDashboardStore()

const isNew = computed(() => !!ui.modalItem?.isNew)
const title = computed(() => (isNew.value ? 'Tambah UMKM' : 'Edit UMKM'))

interface DraftMenuItem {
  name: string
  price: string
  avail: boolean
}

const editingId = ref<number | null>(null)
const saving = ref(false)
const error = ref('')

const draft = reactive({
  name: '',
  cat: CATEGORY_NAMES[0] as CategoryName,
  loc: LOCATION_NAMES[0] as LocationName,
  wa: '',
  ig: '',
  address: '',
  hours: '',
  desc: '',
  menu: [{ name: '', price: '', avail: true }] as DraftMenuItem[],
})

watch(
  () => ui.modalItem,
  (item) => {
    error.value = ''
    if (item && !item.isNew) {
      editingId.value = item.id ?? null
      draft.name = item.name ?? ''
      draft.cat = item.cat ?? CATEGORY_NAMES[0]
      draft.loc = item.loc ?? LOCATION_NAMES[0]
      draft.wa = item.phone || ''
      draft.ig = item.ig || ''
      draft.address = item.address || ''
      draft.hours = item.hours || ''
      draft.desc = item.tag || ''
      draft.menu = (item.items ?? []).map((it: { name: string; price: string; avail?: boolean }) => ({
        name: it.name,
        price: it.price,
        avail: it.avail !== false,
      }))
    } else {
      editingId.value = null
      draft.name = ''
      draft.cat = CATEGORY_NAMES[0]
      draft.loc = LOCATION_NAMES[0]
      draft.wa = ''
      draft.ig = ''
      draft.address = ''
      draft.hours = ''
      draft.desc = ''
      draft.menu = [{ name: '', price: '', avail: true }]
    }
  },
  { immediate: true },
)

function addMenuRow() {
  draft.menu.push({ name: '', price: '', avail: true })
}
function removeMenuRow(i: number) {
  draft.menu.splice(i, 1)
}
function toggleMenuAvail(i: number) {
  draft.menu[i].avail = !draft.menu[i].avail
}

async function saveUmkm() {
  if (!draft.name.trim()) {
    error.value = 'Nama UMKM wajib diisi.'
    return
  }
  saving.value = true
  error.value = ''
  const payload = {
    name: draft.name.trim(),
    category: draft.cat,
    location: draft.loc,
    phone: draft.wa || undefined,
    ig: draft.ig || undefined,
    address: draft.address || undefined,
    hours: draft.hours || undefined,
    tag: draft.desc || undefined,
    items: draft.menu
      .filter((m) => m.name.trim())
      .map((m) => ({ name: m.name.trim(), price: m.price || undefined, available: m.avail })),
  }

  try {
    if (isNew.value) {
      await umkmStore.createUmkm(payload)
    } else if (editingId.value) {
      await umkmStore.updateUmkm(editingId.value, payload)
    }
    await dashboard.fetchOwnerDashboard()
    ui.closeModal()
    alert(
      isNew.value
        ? 'UMKM berhasil dikirim. Menunggu verifikasi admin — akan tampil & aktif setelah disetujui.'
        : 'Perubahan disimpan.',
    )
  } catch (e) {
    error.value = 'Gagal menyimpan UMKM. Periksa kembali data yang diisi.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <BaseModal max-width="max-w-[680px]" @close="ui.closeModal">
    <div class="flex max-h-[90vh] flex-col">
      <div class="flex flex-none items-center justify-between border-b border-border-divider px-[26px] py-[19px]">
        <div class="text-[19px] font-extrabold">{{ title }}</div>
        <button type="button" class="text-2xl leading-none text-text-faint" @click="ui.closeModal">×</button>
      </div>

      <div class="overflow-y-auto px-[26px] py-6">
        <div class="mb-[18px] flex items-start gap-[11px] rounded-xl border border-[#EBD9B4] bg-[#FBF3E4] px-3.5 py-3">
          <span class="text-[17px]">🛡️</span>
          <div class="text-[12.5px] leading-relaxed text-[#7A5B1E]">
            UMKM baru <b class="text-[#8A5A12]">menunggu verifikasi admin</b> sebelum tampil ke publik.
          </div>
        </div>

        <label class="mb-1.5 block text-[13px] font-bold">Nama UMKM</label>
        <input v-model="draft.name" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />

        <div class="grid grid-cols-1 gap-3 mobile:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-[13px] font-bold">Kategori</label>
            <select v-model="draft.cat" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3 py-2.5 font-semibold text-brand-navy">
              <option v-for="c in CATEGORY_NAMES" :key="c">{{ c }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-[13px] font-bold">Wilayah</label>
            <select v-model="draft.loc" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3 py-2.5 font-semibold text-brand-navy">
              <option v-for="l in LOCATION_NAMES" :key="l">{{ l }}</option>
            </select>
          </div>
        </div>

        <label class="mb-1.5 block text-[13px] font-bold">Nomor WhatsApp</label>
        <input v-model="draft.wa" placeholder="0812-xxxx-xxxx" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
        <label class="mb-1.5 block text-[13px] font-bold">Media sosial</label>
        <input v-model="draft.ig" placeholder="@namausaha" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
        <label class="mb-1.5 block text-[13px] font-bold">Alamat lengkap</label>
        <textarea
          v-model="draft.address"
          placeholder="Jl. ... No. ..., kecamatan, Balikpapan"
          class="mb-3.5 min-h-[56px] w-full resize-y rounded-xl border border-border-input bg-white px-3.5 py-2.5"
        />
        <label class="mb-1.5 block text-[13px] font-bold">Jam buka</label>
        <input v-model="draft.hours" placeholder="08.00 – 22.00 WITA" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
        <label class="mb-1.5 block text-[13px] font-bold">Deskripsi</label>
        <textarea v-model="draft.desc" class="mb-4 min-h-20 w-full resize-y rounded-xl border border-border-input bg-white px-3.5 py-2.5" />

        <div class="mb-2.5 flex items-center justify-between">
          <label class="text-[13px] font-bold">Menu / produk &amp; harga</label>
          <button type="button" class="rounded-[9px] bg-brand-blue-tint px-[13px] py-1.5 text-[12.5px] font-bold text-brand-blue" @click="addMenuRow">
            + Tambah
          </button>
        </div>
        <div class="flex flex-col gap-2">
          <div v-for="(m, i) in draft.menu" :key="i" class="flex items-center gap-2">
            <input v-model="m.name" placeholder="Nama menu / produk" class="min-w-0 flex-1 rounded-[10px] border border-border-input bg-white px-3 py-2.5" />
            <input v-model="m.price" placeholder="Harga" class="w-24 flex-none rounded-[10px] border border-border-input bg-white px-3 py-2.5" />
            <button
              type="button"
              class="flex-none rounded-[9px] px-3 py-2.5 text-xs font-bold whitespace-nowrap"
              :style="m.avail ? { background: '#E3EFED', color: '#2E7D6E' } : { background: '#F6E4DF', color: '#C0472F' }"
              @click="toggleMenuAvail(i)"
            >
              {{ m.avail ? 'Tersedia' : 'Habis' }}
            </button>
            <button type="button" class="flex-none rounded-[9px] border border-danger-border px-2.5 py-2.5 font-bold text-danger" @click="removeMenuRow(i)">
              ✕
            </button>
          </div>
        </div>
        <div v-if="draft.menu.length === 0" class="pt-1.5 text-[12.5px] text-text-faint">
          Belum ada menu. Klik "+ Tambah" untuk menambahkan item.
        </div>

        <p v-if="error" class="mt-4 text-[13px] font-semibold text-danger">{{ error }}</p>
      </div>

      <div class="flex flex-none justify-end gap-2.5 border-t border-border-divider px-6 py-[15px]">
        <button type="button" class="rounded-xl border border-border-input px-5 py-2.5 font-bold text-brand-navy" @click="ui.closeModal">Batal</button>
        <button
          type="button"
          class="rounded-xl bg-brand-blue px-[22px] py-2.5 font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="saving"
          @click="saveUmkm"
        >
          {{ saving ? 'Menyimpan…' : 'Simpan' }}
        </button>
      </div>
    </div>
  </BaseModal>
</template>
