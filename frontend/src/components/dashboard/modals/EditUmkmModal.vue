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
import { MENU_ICON_OPTIONS, isPhotoUrl, resolveMenuIcon } from '@/lib/menuIcons'

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
  id?: number
  name: string
  price: string
  avail: boolean
  img: string
  photoFile?: File
  photoPreview?: string
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
  menu: [{ name: '', price: '', avail: true, img: '' }] as DraftMenuItem[],
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
onBeforeUnmount(() => {
  clearPending()
  clearAllMenuPhotoPreviews()
})

watch(
  () => ui.modalItem,
  (item) => {
    error.value = ''
    photoError.value = ''
    clearPending()
    clearAllMenuPhotoPreviews()
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
      draft.menu = (item.items ?? []).map((it: { id?: number; name: string; price: string; img?: string; avail?: boolean }) => ({
        id: it.id,
        name: it.name,
        price: it.price,
        avail: it.avail !== false,
        img: it.img ?? '',
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
      draft.menu = [{ name: '', price: '', avail: true, img: '' }]
      photos.value = []
    }
  },
  { immediate: true },
)

function clearMenuPhotoPreview(item: DraftMenuItem) {
  if (item.photoFile && item.photoPreview) URL.revokeObjectURL(item.photoPreview)
  item.photoFile = undefined
  item.photoPreview = undefined
}

function clearAllMenuPhotoPreviews() {
  draft.menu.forEach(clearMenuPhotoPreview)
}

function addMenuRow() {
  draft.menu.push({ name: '', price: '', avail: true, img: '' })
}
function removeMenuRow(i: number) {
  clearMenuPhotoPreview(draft.menu[i])
  draft.menu.splice(i, 1)
}

function selectMenuIcon(item: DraftMenuItem, iconKey: string) {
  clearMenuPhotoPreview(item)
  item.img = iconKey
}

function removeProductPhoto(item: DraftMenuItem) {
  clearMenuPhotoPreview(item)
  if (isPhotoUrl(item.img)) {
    item.img = ''
  }
}

function onPickProductPhoto(e: Event, item: DraftMenuItem) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) return

  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    error.value = 'Foto produk harus berformat JPG, PNG, atau WebP.'
    return
  }
  if (file.size > 4 * 1024 * 1024) {
    error.value = 'Ukuran foto produk maksimal 4 MB.'
    return
  }

  clearMenuPhotoPreview(item)
  item.photoFile = file
  item.photoPreview = URL.createObjectURL(file)
  error.value = ''
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
      .map((m) => ({
        name: m.name.trim(),
        price: m.price.trim() || null,
        img: m.photoFile ? null : (m.img || null),
        available: m.avail,
      })),
  }

  const wasNew = isNew.value
  let photoFailure = ''
  try {
    let saved: Awaited<ReturnType<typeof umkmStore.createUmkm>> | null = null
    if (wasNew) {
      saved = await umkmStore.createUmkm(payload)
      if (pending.value.length) {
        try {
          await umkmStore.uploadPhotos(saved.id, pending.value.map((p) => p.file))
        } catch (err) {
          photoFailure = messageOf(err, 'Foto usaha gagal diunggah.')
        }
      }
    } else if (editingId.value) {
      saved = await umkmStore.updateUmkm(editingId.value, payload)
    }

    // Product photos can be uploaded by both Admin and UMKM Owner
    if (saved) {
      const namedRows = draft.menu.filter((m) => m.name.trim())
      for (let index = 0; index < namedRows.length; index++) {
        const row = namedRows[index]
        const savedItem = saved.items[index]
        if (!row.photoFile) continue
        if (!savedItem?.id) {
          photoFailure = 'Foto produk tidak dapat dipasangkan dengan data produk yang tersimpan.'
          continue
        }
        try {
          row.img = await umkmStore.uploadProductPhoto(saved.id, savedItem.id, row.photoFile)
        } catch (err) {
          photoFailure = messageOf(err, 'Foto produk gagal diunggah.')
        }
      }
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
  <BaseModal max-width="max-w-[700px]" @close="ui.closeModal">
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

        <!-- Foto galeri (max 8) -->
        <div class="mb-4">
          <div class="mb-1 flex items-center justify-between">
            <label class="text-[13px] font-bold">Foto UMKM</label>
            <span class="text-xs font-semibold text-text-faint">{{ photoCount }} / {{ MAX_PHOTOS }}</span>
          </div>
          <p class="mb-2 text-xs text-text-faint">
            Foto pertama otomatis menjadi sampul. JPG, PNG, atau WebP, maksimal 4 MB per foto.
          </p>

          <div class="grid grid-cols-3 gap-2.5 mobile:grid-cols-4">
            <!-- Existing photos -->
            <div
              v-for="(photo, i) in photos"
              :key="photo.id"
              class="group relative aspect-4/3 overflow-hidden rounded-xl border border-border-divider bg-[#FAFAF8]"
            >
              <img :src="photo.url" :alt="`Foto ${i + 1}`" class="h-full w-full object-cover" />
              <span
                v-if="i === 0"
                class="absolute top-1.5 left-1.5 rounded-md bg-[rgba(15,30,45,.75)] px-1.5 py-0.5 text-[10.5px] font-bold text-white"
              >
                Sampul
              </span>
              <div
                class="absolute inset-0 flex items-center justify-center gap-1.5 bg-[rgba(15,30,45,.55)] opacity-0 transition-opacity group-hover:opacity-100"
              >
                <button
                  v-if="i !== 0"
                  type="button"
                  title="Jadikan sampul"
                  class="rounded-lg bg-white/90 px-2 py-1 text-[11px] font-bold text-brand-navy hover:bg-white"
                  :disabled="photoBusy"
                  @click="makeCover(photo)"
                >
                  Sampul
                </button>
                <button
                  type="button"
                  title="Hapus foto"
                  class="rounded-lg bg-[#FFEBEA] p-1.5 text-danger hover:bg-[#FFD6D3]"
                  :disabled="photoBusy"
                  @click="removePhoto(photo)"
                >
                  <CloseIcon size="13px" />
                </button>
              </div>
            </div>

            <!-- Pending photos for new UMKM -->
            <div
              v-for="(p, i) in pending"
              :key="p.preview"
              class="group relative aspect-4/3 overflow-hidden rounded-xl border border-border-divider bg-[#FAFAF8]"
            >
              <img :src="p.preview" :alt="`Foto baru ${i + 1}`" class="h-full w-full object-cover" />
              <span
                v-if="photos.length === 0 && i === 0"
                class="absolute top-1.5 left-1.5 rounded-md bg-[rgba(15,30,45,.75)] px-1.5 py-0.5 text-[10.5px] font-bold text-white"
              >
                Sampul
              </span>
              <button
                type="button"
                title="Batalkan foto ini"
                class="absolute top-1.5 right-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-[rgba(15,30,45,.7)] text-white hover:bg-danger"
                @click="removePending(i)"
              >
                <CloseIcon size="11px" />
              </button>
            </div>

            <!-- Upload trigger tile -->
            <label
              v-if="photoCount < MAX_PHOTOS"
              class="flex aspect-4/3 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-[#DDD7C9] bg-[#FAF8F3] text-center transition-colors hover:border-brand-blue hover:bg-white"
              :class="{ 'pointer-events-none opacity-50': photoBusy }"
            >
              <input
                type="file"
                multiple
                accept="image/jpeg,image/png,image/webp"
                class="hidden"
                :disabled="photoBusy"
                @change="onPickPhotos"
              />
              <span class="text-xs font-bold text-brand-blue">+ Tambah foto</span>
              <span class="text-[11px] text-text-faint">{{ MAX_PHOTOS - photoCount }} slot</span>
            </label>
          </div>

          <p v-if="photoError" class="mt-2 text-xs font-semibold text-danger">{{ photoError }}</p>
        </div>

        <div class="grid grid-cols-1 gap-3 mobile:grid-cols-2">
          <div>
            <label class="mb-1.5 block text-[13px] font-bold" for="umkm-wa">Nomor HP / WhatsApp</label>
            <input id="umkm-wa" v-model="draft.wa" placeholder="08xxxxxxxxxx" class="mb-3.5 w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5" />
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

        <!-- Menu / Produk & Harga -->
        <div class="mb-2.5 flex items-center justify-between">
          <div>
            <label class="text-[13px] font-bold">Menu / produk &amp; harga</label>
            <p class="text-[11.5px] text-text-faint">Pilih ikon default atau unggah foto asli untuk setiap menu.</p>
          </div>
          <button type="button" class="flex items-center gap-1 rounded-[9px] bg-brand-blue-tint px-[13px] py-1.5 text-[12.5px] font-bold text-brand-blue" @click="addMenuRow">
            <PlusIcon size="12px" /> Tambah
          </button>
        </div>

        <div class="flex flex-col gap-3">
          <div v-for="(m, i) in draft.menu" :key="i" class="rounded-xl border border-border-divider bg-[#FAFAFA] p-3.5">
            <!-- Row 1: Item name, price, availability, delete -->
            <div class="flex items-center gap-2">
              <input v-model="m.name" placeholder="Nama menu / produk" class="min-w-0 flex-1 rounded-[10px] border border-border-input bg-white px-3 py-2 text-sm font-semibold text-brand-navy" />
              <input v-model="m.price" placeholder="Harga" class="w-24 flex-none rounded-[10px] border border-border-input bg-white px-3 py-2 text-sm" />
              <button
                type="button"
                class="flex-none rounded-[9px] px-3 py-2 text-xs font-bold whitespace-nowrap transition-colors"
                :style="m.avail ? { background: '#E3EFED', color: '#2E7D6E' } : { background: '#F6E4DF', color: '#C0472F' }"
                @click="toggleMenuAvail(i)"
              >
                {{ m.avail ? 'Tersedia' : 'Habis' }}
              </button>
              <button type="button" aria-label="Hapus baris" class="flex-none rounded-[9px] border border-danger-border px-2.5 py-2 font-bold text-danger hover:bg-danger-tint" @click="removeMenuRow(i)">
                <CloseIcon size="13px" />
              </button>
            </div>

            <!-- Row 2: Media Management (Icon Selector OR Real Photo Upload) for BOTH Admin and Owner -->
            <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-border-divider/70 pt-2.5">
              <!-- Visual indicator -->
              <div class="flex items-center gap-2.5">
                <!-- If has photo preview or real photo URL -->
                <template v-if="m.photoPreview || isPhotoUrl(m.img)">
                  <img
                    :src="m.photoPreview || m.img"
                    :alt="`Foto ${m.name || 'produk'}`"
                    class="h-11 w-11 flex-none rounded-lg border border-border-divider object-cover shadow-xs"
                  />
                  <div>
                    <div class="inline-flex items-center gap-1 rounded-md bg-[#E8F3EE] px-2 py-0.5 text-[11px] font-bold text-[#20695A]">
                      <span class="h-1.5 w-1.5 rounded-full bg-[#20695A]" /> Foto Asli
                    </div>
                    <div class="mt-0.5 flex items-center gap-2">
                      <label class="cursor-pointer text-[11px] font-bold text-brand-blue hover:underline">
                        Ganti foto
                        <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPickProductPhoto($event, m)" />
                      </label>
                      <span class="text-[11px] text-text-faint">·</span>
                      <button type="button" class="text-[11px] font-bold text-danger hover:underline" @click="removeProductPhoto(m)">
                        Gunakan Ikon
                      </button>
                    </div>
                  </div>
                </template>

                <!-- If icon mode -->
                <template v-else>
                  <div class="flex h-11 w-11 flex-none items-center justify-center rounded-lg border border-[#EDE7D9] bg-[#FBF8F1] text-brand-gold shadow-xs">
                    <component :is="resolveMenuIcon(m.img, m.name).icon" size="22px" />
                  </div>
                  <div>
                    <div class="text-[11.5px] font-bold text-brand-navy">
                      Ikon: <span class="font-extrabold text-brand-gold">{{ resolveMenuIcon(m.img, m.name).shortLabel }}</span>
                    </div>
                    <div class="text-[11px] text-text-faint">Pilih ikon atau unggah foto asli</div>
                  </div>
                </template>
              </div>

              <!-- Action Controls: Icon Selector Chips & Upload Button -->
              <div class="flex flex-wrap items-center gap-1.5">
                <!-- Icon choice chips (visible when not using a photo) -->
                <div v-if="!m.photoPreview && !isPhotoUrl(m.img)" class="flex items-center gap-1 rounded-lg border border-border-input bg-white p-1">
                  <button
                    v-for="opt in MENU_ICON_OPTIONS"
                    :key="opt.key"
                    type="button"
                    class="flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-bold transition-all"
                    :class="
                      resolveMenuIcon(m.img, m.name).key === opt.key
                        ? 'bg-brand-navy text-white shadow-xs'
                        : 'text-text-secondary hover:bg-gray-100 hover:text-brand-navy'
                    "
                    :title="opt.label"
                    @click="selectMenuIcon(m, opt.key)"
                  >
                    <component :is="opt.icon" size="12px" />
                    <span>{{ opt.shortLabel }}</span>
                  </button>
                </div>

                <!-- Upload Real Photo Button -->
                <label
                  v-if="!m.photoPreview && !isPhotoUrl(m.img)"
                  class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-border-input bg-white px-2.5 py-1.5 text-xs font-bold text-brand-navy shadow-xs transition-colors hover:border-brand-blue hover:text-brand-blue"
                  title="Unggah foto asli untuk menu ini"
                >
                  <PlusIcon size="12px" />
                  <span>Foto Asli</span>
                  <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onPickProductPhoto($event, m)" />
                </label>
              </div>
            </div>
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
