<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useUmkmStore } from '@/stores/umkm'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/dashboard'
import { ApiError } from '@/lib/api'
import { CATEGORY_NAMES, LOCATION_NAMES } from '@/data/categories'
import type { CategoryName, LocationName, UmkmPhoto, UmkmStatus } from '@/types'
import BaseModal from '@/components/shared/BaseModal.vue'
import ShieldIcon from '@/components/shared/ShieldIcon.vue'
import CloseIcon from '@/components/shared/CloseIcon.vue'
import PlusIcon from '@/components/shared/PlusIcon.vue'

const ui = useUiStore()
const umkmStore = useUmkmStore()
const auth = useAuthStore()
const dashboard = useDashboardStore()

const isNew = computed(() => !!ui.modalItem?.isNew)
const isAdmin = computed(() => auth.isAdmin)
const title = computed(() => (isNew.value ? 'Tambah UMKM' : 'Edit UMKM'))

/** Mirrors UmkmPhotoController on the API. */
const MAX_PHOTOS = 8
const MAX_PHOTO_BYTES = 4 * 1024 * 1024
const PHOTO_TYPES = ['image/jpeg', 'image/png', 'image/webp']

interface DraftMenuItem {
  name: string
  price: string
  avail: boolean
}

const STATUS_TO_API: Record<UmkmStatus, string> = { Aktif: 'aktif', Libur: 'libur', Tutup: 'tutup' }

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
  price: '',
  listLabel: '',
  status: 'Aktif' as UmkmStatus,
  desc: '',
  menu: [{ name: '', price: '', avail: true }] as DraftMenuItem[],
})

// ---- Foto ----
/** Photos already on the server (existing UMKM). */
const photos = ref<UmkmPhoto[]>([])
/** Files picked for a new UMKM - uploaded right after it is created. */
const pending = ref<{ file: File; preview: string }[]>([])
const photoBusy = ref(false)
const photoError = ref('')

const photoCount = computed(() => photos.value.length + pending.value.length)

function clearPending() {
  pending.value.forEach((p) => URL.revokeObjectURL(p.preview))
  pending.value = []
}
onBeforeUnmount(clearPending)

watch(
  () => ui.modalItem,
  (item) => {
    error.value = ''
    photoError.value = ''
    clearPending()
    if (item && !item.isNew) {
      editingId.value = item.id ?? null
      draft.name = item.name ?? ''
      draft.cat = item.cat ?? CATEGORY_NAMES[0]
      draft.loc = item.loc ?? LOCATION_NAMES[0]
      draft.wa = item.phone || ''
      draft.ig = item.ig || ''
      draft.address = item.address || ''
      draft.hours = item.hours || ''
      draft.price = item.priceLabel || ''
      draft.listLabel = item.listLabel || ''
      draft.status = (['Aktif', 'Libur', 'Tutup'] as UmkmStatus[]).includes(item.status) ? item.status : 'Aktif'
      draft.desc = item.tag || ''
      draft.menu = (item.items ?? []).map((it: { name: string; price: string; avail?: boolean }) => ({
        name: it.name,
        price: it.price,
        avail: it.avail !== false,
      }))
      photos.value = [...(item.photos ?? [])]
    } else {
      editingId.value = null
      draft.name = ''
      draft.cat = CATEGORY_NAMES[0]
      draft.loc = LOCATION_NAMES[0]
      draft.wa = ''
      draft.ig = ''
      draft.address = ''
      draft.hours = ''
      draft.price = ''
      draft.listLabel = ''
      draft.status = 'Aktif'
      draft.desc = ''
      draft.menu = [{ name: '', price: '', avail: true }]
      photos.value = []
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

function messageOf(e: unknown, fallback: string) {
  return e instanceof ApiError ? e.firstError : fallback
}

/** Check files in the browser first, so a wrong type or size is explained before uploading. */
function acceptable(files: File[]): File[] {
  photoError.value = ''
  const room = MAX_PHOTOS - photoCount.value
  if (room <= 0) {
    photoError.value = `Maksimal ${MAX_PHOTOS} foto per UMKM. Hapus foto lama terlebih dahulu.`
    return []
  }
  const ok: File[] = []
  for (const file of files) {
    if (!PHOTO_TYPES.includes(file.type)) {
      photoError.value = `"${file.name}" bukan JPG, PNG, atau WebP.`
    } else if (file.size > MAX_PHOTO_BYTES) {
      photoError.value = `"${file.name}" lebih dari 4 MB.`
    } else {
      ok.push(file)
    }
  }
  if (ok.length > room) {
    photoError.value = `Hanya ${room} foto lagi yang bisa ditambahkan.`
    return ok.slice(0, room)
  }
  return ok
}

async function onPickPhotos(e: Event) {
  const input = e.target as HTMLInputElement
  const files = acceptable(Array.from(input.files ?? []))
  input.value = ''
  if (!files.length) return

  if (!editingId.value) {
    pending.value.push(...files.map((file) => ({ file, preview: URL.createObjectURL(file) })))
    return
  }

  photoBusy.value = true
  try {
    const updated = await umkmStore.uploadPhotos(editingId.value, files)
    photos.value = updated.photos
  } catch (err) {
    photoError.value = messageOf(err, 'Foto gagal diunggah. Coba lagi.')
  } finally {
    photoBusy.value = false
  }
}

async function removePhoto(photo: UmkmPhoto) {
  if (!editingId.value || !confirm('Hapus foto ini?')) return
  photoBusy.value = true
  try {
    photos.value = (await umkmStore.deletePhoto(editingId.value, photo.id)).photos
  } catch (err) {
    photoError.value = messageOf(err, 'Foto gagal dihapus.')
  } finally {
    photoBusy.value = false
  }
}

async function makeCover(photo: UmkmPhoto) {
  if (!editingId.value) return
  photoBusy.value = true
  try {
    photos.value = (await umkmStore.setCoverPhoto(editingId.value, photo.id)).photos
  } catch (err) {
    photoError.value = messageOf(err, 'Gagal menjadikan sampul.')
  } finally {
    photoBusy.value = false
  }
}

function removePending(i: number) {
  URL.revokeObjectURL(pending.value[i].preview)
  pending.value.splice(i, 1)
}

async function refreshDashboard() {
  if (isAdmin.value) await dashboard.fetchAdminDashboard()
  else await dashboard.fetchOwnerDashboard()
  // The public catalogue may have changed (edits, admin-created UMKM).
  await umkmStore.fetchAll(true)
}

async function saveUmkm() {
  if (draft.name.trim().length < 2) {
    error.value = 'Nama UMKM wajib diisi (minimal 2 karakter).'
    return
  }
  const badItem = draft.menu.find((m) => !m.name.trim() && m.price.trim())
  if (badItem) {
    error.value = 'Setiap produk yang punya harga wajib diberi nama.'
    return
  }
  saving.value = true
  error.value = ''
  const payload = {
    name: draft.name.trim(),
    category: draft.cat,
    location: draft.loc,
    phone: draft.wa.trim() || null,
    ig: draft.ig.trim() || null,
    address: draft.address.trim() || null,
    hours: draft.hours.trim() || null,
    price_label: draft.price.trim() || null,
    list_label: draft.listLabel.trim() || null,
    status: STATUS_TO_API[draft.status],
    tag: draft.desc.trim() || null,
    items: draft.menu
      .filter((m) => m.name.trim())
      .map((m) => ({ name: m.name.trim(), price: m.price.trim() || null, available: m.avail })),
  }

  const wasNew = isNew.value
  let photoFailure = ''
  try {
    if (wasNew) {
      const created = await umkmStore.createUmkm(payload)
      if (pending.value.length) {
        try {
          await umkmStore.uploadPhotos(created.id, pending.value.map((p) => p.file))
        } catch (err) {
          photoFailure = messageOf(err, 'Foto gagal diunggah.')
        }
      }
    } else if (editingId.value) {
      await umkmStore.updateUmkm(editingId.value, payload)
    }
  } catch (e) {
    error.value = messageOf(e, 'Gagal menyimpan UMKM. Periksa kembali data yang diisi.')
    saving.value = false
    return
  }

  await refreshDashboard().catch(() => undefined)
  saving.value = false
  ui.closeModal()

  let message = 'Perubahan disimpan.'
  if (wasNew) {
    message = isAdmin.value
      ? 'UMKM berhasil ditambahkan dan langsung tampil di website.'
      : 'UMKM berhasil dikirim. Menunggu verifikasi admin - akan tampil setelah disetujui.'
  }
  alert(photoFailure ? `${message}\n\nNamun foto gagal diunggah: ${photoFailure} Unggah ulang lewat "Kelola & edit".` : message)
}
</script>

<template>
  <BaseModal max-width="max-w-[680px]" @close="ui.closeModal">
    <div class="flex max-h-[90vh] flex-col">
      <div class="flex flex-none items-center justify-between border-b border-border-divider px-[26px] py-[19px]">
        <div class="text-[19px] font-extrabold">{{ title }}</div>
        <button type="button" aria-label="Tutup" class="text-text-faint" @click="ui.closeModal"><CloseIcon size="20px" /></button>
      </div>

      <div class="overflow-y-auto px-[26px] py-6">
        <div v-if="isNew && !isAdmin" class="mb-[18px] flex items-start gap-[11px] rounded-xl border border-[#EBD9B4] bg-[#FBF3E4] px-3.5 py-3">
          <ShieldIcon size="17px" class="flex-none text-[#B07A1E]" />
          <div class="text-[12.5px] leading-relaxed text-[#7A5B1E]">
            UMKM baru <b class="text-[#8A5A12]">menunggu verifikasi admin</b> sebelum tampil ke publik.
          </div>
        </div>

        <label class="mb-1.5 block text-[13px] font-bold" for="umkm-name">Nama UMKM *</label>
        <input id="umkm-name" v-model="draft.name" maxlength="255" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />

        <div class="grid grid-cols-1 gap-3 mobile:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-cat">Kategori *</label>
            <select id="umkm-cat" v-model="draft.cat" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3 py-2.5 font-semibold text-brand-navy">
              <option v-for="c in CATEGORY_NAMES" :key="c">{{ c }}</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-loc">Wilayah *</label>
            <select id="umkm-loc" v-model="draft.loc" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3 py-2.5 font-semibold text-brand-navy">
              <option v-for="l in LOCATION_NAMES" :key="l">{{ l }}</option>
            </select>
          </div>
        </div>

        <!-- Foto -->
        <div class="mb-1.5 flex items-center justify-between">
          <label class="text-[13px] font-bold">Foto usaha ({{ photoCount }}/{{ MAX_PHOTOS }})</label>
          <span class="text-[12px] text-text-faint">JPG/PNG/WebP, maks. 4 MB</span>
        </div>
        <div class="mb-1.5 flex flex-wrap gap-2.5">
          <div v-for="(p, i) in photos" :key="p.id" class="relative h-[86px] w-[86px]">
            <img :src="p.url" alt="" class="h-full w-full rounded-xl object-cover" />
            <span v-if="i === 0" class="absolute bottom-1 left-1 rounded-md bg-brand-navy/80 px-1.5 py-0.5 text-[10px] font-bold text-white">Sampul</span>
            <button
              v-else
              type="button"
              class="absolute bottom-1 left-1 rounded-md bg-white/90 px-1.5 py-0.5 text-[10px] font-bold text-brand-navy disabled:opacity-50"
              :disabled="photoBusy"
              @click="makeCover(p)"
            >
              Jadikan sampul
            </button>
            <button
              type="button"
              aria-label="Hapus foto"
              class="absolute -top-1.5 -right-1.5 flex h-[22px] w-[22px] items-center justify-center rounded-full border border-white bg-danger text-white disabled:opacity-50"
              :disabled="photoBusy"
              @click="removePhoto(p)"
            >
              <CloseIcon size="12px" />
            </button>
          </div>
          <div v-for="(p, i) in pending" :key="p.preview" class="relative h-[86px] w-[86px]">
            <img :src="p.preview" alt="" class="h-full w-full rounded-xl object-cover opacity-80" />
            <span class="absolute bottom-1 left-1 rounded-md bg-white/90 px-1.5 py-0.5 text-[10px] font-bold text-brand-navy">Diunggah saat simpan</span>
            <button
              type="button"
              aria-label="Batalkan foto"
              class="absolute -top-1.5 -right-1.5 flex h-[22px] w-[22px] items-center justify-center rounded-full border border-white bg-brand-navy text-white"
              @click="removePending(i)"
            >
              <CloseIcon size="12px" />
            </button>
          </div>
          <label
            v-if="photoCount < MAX_PHOTOS"
            class="flex h-[86px] w-[86px] cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-[#D6CFC0] text-[11px] font-semibold text-text-faint hover:border-brand-blue hover:text-brand-blue"
            :class="photoBusy ? 'pointer-events-none opacity-60' : ''"
          >
            <PlusIcon size="18px" />
            {{ photoBusy ? 'Mengunggah…' : 'Tambah foto' }}
            <input type="file" accept="image/jpeg,image/png,image/webp" multiple class="hidden" @change="onPickPhotos" />
          </label>
        </div>
        <p v-if="photoError" class="mb-2 text-[12.5px] font-semibold text-danger">{{ photoError }}</p>
        <p class="mb-3.5 text-[12px] text-text-faint">Foto pertama menjadi sampul di kartu dan halaman detail.</p>

        <div class="grid grid-cols-1 gap-3 mobile:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-wa">Nomor WhatsApp</label>
            <input id="umkm-wa" v-model="draft.wa" type="tel" placeholder="0812-xxxx-xxxx" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
          </div>
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-ig">Instagram</label>
            <input id="umkm-ig" v-model="draft.ig" placeholder="@namausaha" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
          </div>
        </div>
        <label class="mb-1.5 block text-[13px] font-bold" for="umkm-address">Alamat lengkap</label>
        <textarea
          id="umkm-address"
          v-model="draft.address"
          maxlength="255"
          placeholder="Jl. ... No. ..., kelurahan, Balikpapan"
          class="mb-3.5 min-h-[56px] w-full resize-y rounded-xl border border-border-input bg-white px-3.5 py-2.5"
        />
        <div class="grid grid-cols-1 gap-3 mobile:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-hours">Jam buka</label>
            <input id="umkm-hours" v-model="draft.hours" placeholder="08.00 – 22.00 WITA" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
          </div>
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-price">Kisaran harga</label>
            <input id="umkm-price" v-model="draft.price" placeholder="Rp15–50rb" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
          </div>
        </div>
        <div class="grid grid-cols-1 gap-3 mobile:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-status">Status buka</label>
            <select id="umkm-status" v-model="draft.status" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3 py-2.5 font-semibold text-brand-navy">
              <option>Aktif</option>
              <option>Libur</option>
              <option>Tutup</option>
            </select>
          </div>
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-listlabel">Judul daftar produk</label>
            <input id="umkm-listlabel" v-model="draft.listLabel" placeholder="Menu andalan / Layanan / Tipe kamar" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
          </div>
        </div>
        <label class="mb-1.5 block text-[13px] font-bold" for="umkm-desc">Deskripsi</label>
        <textarea id="umkm-desc" v-model="draft.desc" maxlength="2000" class="mb-4 min-h-20 w-full resize-y rounded-xl border border-border-input bg-white px-3.5 py-2.5" />

        <div class="mb-2.5 flex items-center justify-between">
          <label class="text-[13px] font-bold">Menu / produk &amp; harga</label>
          <button type="button" class="flex items-center gap-1 rounded-[9px] bg-brand-blue-tint px-[13px] py-1.5 text-[12.5px] font-bold text-brand-blue" @click="addMenuRow">
            <PlusIcon size="12px" /> Tambah
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
            <button type="button" aria-label="Hapus baris" class="flex-none rounded-[9px] border border-danger-border px-2.5 py-2.5 font-bold text-danger" @click="removeMenuRow(i)">
              <CloseIcon size="13px" />
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
          :disabled="saving || photoBusy"
          @click="saveUmkm"
        >
          {{ saving ? 'Menyimpan…' : 'Simpan' }}
        </button>
      </div>
    </div>
  </BaseModal>
</template>
