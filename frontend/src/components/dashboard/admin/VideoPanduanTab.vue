<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useGuideVideoStore } from '@/stores/guideVideo'
import { ApiError } from '@/lib/api'
import type { GuideVideo } from '@/types'

const store = useGuideVideoStore()

const fileInput = ref<HTMLInputElement | null>(null)
const file = ref<File | null>(null)
const title = ref('')
const description = ref('')
const formError = ref('')
const savedMessage = ref('')

const maxLabel = computed(() => {
  const bytes = store.limits?.maxBytes
  return bytes ? `${(bytes / 1024 / 1024).toFixed(0)} MB` : '—'
})

function humanSize(bytes: number): string {
  const mb = bytes / 1024 / 1024
  return mb >= 1 ? `${mb.toFixed(1).replace('.', ',')} MB` : `${Math.round(bytes / 1024)} KB`
}

function pickFile(event: Event) {
  const input = event.target as HTMLInputElement
  const chosen = input.files?.[0] ?? null
  file.value = chosen
  formError.value = ''

  // Reject the obvious cases here rather than after a long upload that the
  // server was always going to refuse.
  if (chosen) {
    const max = store.limits?.maxBytes
    if (max && chosen.size > max) {
      formError.value = `Ukuran ${humanSize(chosen.size)} melebihi batas ${maxLabel.value}.`
    }
    if (title.value.trim() === '') {
      title.value = chosen.name.replace(/\.[^.]+$/, '')
    }
  }
}

async function submit() {
  if (!file.value) {
    formError.value = 'Pilih berkas video terlebih dahulu.'
    return
  }
  if (!title.value.trim()) {
    formError.value = 'Judul video wajib diisi.'
    return
  }
  formError.value = ''
  savedMessage.value = ''

  try {
    await store.upload(file.value, { title: title.value.trim(), description: description.value.trim() || null })
    savedMessage.value = 'Video panduan berhasil diunggah dan kini tampil di halaman Panduan.'
    file.value = null
    title.value = ''
    description.value = ''
    if (fileInput.value) fileInput.value.value = ''
  } catch (e) {
    formError.value = e instanceof ApiError ? e.firstError : 'Gagal mengunggah video. Coba lagi.'
  }
}

const rowBusy = ref<number | null>(null)

async function makeActive(v: GuideVideo) {
  rowBusy.value = v.id
  try {
    await store.updateVideo(v.id, { active: true })
    savedMessage.value = `"${v.title}" kini tampil di halaman Panduan.`
  } catch (e) {
    formError.value = e instanceof ApiError ? e.firstError : 'Gagal mengganti video aktif.'
  } finally {
    rowBusy.value = null
  }
}

async function remove(v: GuideVideo) {
  if (!confirm(`Hapus video "${v.title}"? Berkasnya ikut terhapus dan tidak bisa dikembalikan.`)) return
  rowBusy.value = v.id
  try {
    await store.deleteVideo(v.id)
    savedMessage.value = 'Video dihapus.'
  } catch {
    formError.value = 'Gagal menghapus video. Coba lagi.'
  } finally {
    rowBusy.value = null
  }
}

onMounted(() => {
  store.fetchAdminVideos()
  store.fetchLimits()
})
</script>

<template>
  <div class="mb-[22px]">
    <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Video Panduan</h1>
    <p class="mt-[7px] text-text-muted">
      Unggah video cara mendaftarkan UMKM — video yang aktif langsung tampil di halaman
      <RouterLink :to="{ name: 'panduan' }" class="font-bold text-brand-blue">Panduan</RouterLink>.
    </p>
  </div>

  <!-- Form unggah -->
  <div class="rounded-[18px] border border-border-card bg-white p-5 shadow-[0_4px_16px_rgba(19,50,77,.04)]">
    <div class="text-[12.5px] font-extrabold tracking-[.06em] text-text-faint uppercase">Unggah video baru</div>

    <div class="mt-3.5">
      <label for="guide-file" class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Berkas video</label>
      <input
        id="guide-file"
        ref="fileInput"
        type="file"
        accept="video/mp4,video/webm,video/ogg,video/quicktime"
        :disabled="store.uploading"
        class="w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-[14px] text-brand-navy file:mr-3 file:rounded-lg file:border-0 file:bg-brand-blue-tint file:px-3 file:py-1.5 file:text-[13px] file:font-bold file:text-brand-blue disabled:opacity-60"
        @change="pickFile"
      />
      <p class="mt-1.5 text-[12px] leading-relaxed text-text-faint">
        Format MP4, WebM, OGG, atau MOV. Maksimal {{ maxLabel }}.
        <span v-if="file" class="font-semibold text-brand-navy">Dipilih: {{ file.name }} ({{ humanSize(file.size) }}).</span>
      </p>
      <p v-if="store.limits?.phpLimited" class="mt-1.5 text-[12px] leading-relaxed font-semibold text-gold">
        Batas server (php.ini) saat ini hanya {{ maxLabel }}. Untuk video lebih besar, naikkan
        <span class="font-mono">upload_max_filesize</span> dan <span class="font-mono">post_max_size</span>, lalu mulai ulang server.
      </p>
    </div>

    <div class="mt-3.5 grid grid-cols-1 gap-3.5 tablet:grid-cols-2">
      <div>
        <label for="guide-title" class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Judul</label>
        <input
          id="guide-title"
          v-model="title"
          placeholder="Mis. Cara mendaftarkan UMKM di NearBy"
          :disabled="store.uploading"
          class="w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy disabled:opacity-60"
        />
      </div>
      <div>
        <label for="guide-desc" class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">
          Keterangan <span class="font-normal text-text-faint">(opsional)</span>
        </label>
        <input
          id="guide-desc"
          v-model="description"
          placeholder="Mis. Durasi 2 menit, dari buat akun sampai verifikasi"
          :disabled="store.uploading"
          class="w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy disabled:opacity-60"
        />
      </div>
    </div>

    <!-- Progres unggah -->
    <div v-if="store.uploading" class="mt-4">
      <div class="mb-1.5 flex items-center justify-between text-[12.5px] font-bold text-brand-navy">
        <span>Mengunggah…</span>
        <span>{{ store.uploadProgress }}%</span>
      </div>
      <div
        class="h-2.5 w-full overflow-hidden rounded-full bg-[#E8EDF3]"
        role="progressbar"
        :aria-valuenow="store.uploadProgress ?? 0"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        <div class="h-full rounded-full bg-brand-blue transition-[width] duration-200" :style="{ width: `${store.uploadProgress}%` }" />
      </div>
    </div>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-border-divider-2 pt-3.5">
      <div class="min-h-[20px] text-[13px] leading-relaxed font-semibold">
        <span v-if="formError" class="text-danger">{{ formError }}</span>
        <span v-else-if="savedMessage" class="text-teal-deep">{{ savedMessage }}</span>
      </div>
      <button
        type="button"
        class="rounded-[11px] bg-brand-blue px-5 py-2.5 font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="store.uploading"
        @click="submit"
      >
        {{ store.uploading ? 'Mengunggah…' : 'Unggah video' }}
      </button>
    </div>
  </div>

  <!-- Daftar video terunggah -->
  <div class="mt-6 mb-3 text-[12.5px] font-extrabold tracking-[.06em] text-text-faint uppercase">
    Video terunggah
  </div>

  <div v-if="store.adminLoading && !store.adminVideos.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center text-text-faint">
    Memuat…
  </div>

  <div v-else-if="!store.adminVideos.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center">
    <div class="text-[42px]">🎬</div>
    <div class="mt-3 text-[19px] font-extrabold text-brand-navy">Belum ada video panduan</div>
    <p class="mx-auto mt-2 max-w-[440px] text-[14.5px] leading-relaxed text-text-muted">
      Unggah video pertama di atas. Sampai ada yang diunggah, halaman Panduan menampilkan kotak placeholder.
    </p>
  </div>

  <div v-else class="flex flex-col gap-3.5">
    <div
      v-for="v in store.adminVideos"
      :key="v.id"
      class="rounded-[18px] border bg-white p-5 shadow-[0_4px_16px_rgba(19,50,77,.04)]"
      :class="v.active ? 'border-teal' : 'border-border-card'"
    >
      <div class="flex flex-wrap items-center gap-2.5">
        <span
          class="rounded-full px-[11px] py-1 text-[11.5px] font-bold"
          :style="v.active ? { background: '#E3EFED', color: '#2E7D6E' } : { background: '#EEEADF', color: '#8A8578' }"
        >
          {{ v.active ? 'Tampil di halaman Panduan' : 'Tidak tampil' }}
        </span>
        <span v-if="v.fileMissing" class="rounded-full bg-danger-tint px-[11px] py-1 text-[11.5px] font-bold text-danger">
          Berkas hilang dari penyimpanan
        </span>
        <span class="ml-auto text-[12.5px] text-text-faint">
          {{ v.sizeLabel }} · {{ v.uploadedAt }}<span v-if="v.uploadedBy"> · oleh {{ v.uploadedBy }}</span>
        </span>
      </div>

      <div class="mt-3.5 grid grid-cols-1 gap-4 tablet:grid-cols-[260px_1fr]">
        <video
          v-if="!v.fileMissing"
          :src="v.url"
          controls
          preload="metadata"
          class="aspect-video w-full rounded-[12px] bg-black"
        />
        <div
          v-else
          class="flex aspect-video w-full items-center justify-center rounded-[12px] bg-[#F3F1EA] text-[13px] font-semibold text-text-faint"
        >
          Pratinjau tidak tersedia
        </div>

        <div class="min-w-0">
          <div class="text-[16px] font-extrabold text-brand-navy">{{ v.title }}</div>
          <p v-if="v.description" class="mt-1 text-[14px] leading-relaxed text-text-secondary">{{ v.description }}</p>
          <div class="mt-1.5 font-mono text-[12px] break-all text-text-faint">{{ v.originalName }}</div>

          <div class="mt-3.5 flex flex-wrap gap-2">
            <button
              v-if="!v.active && !v.fileMissing"
              type="button"
              class="rounded-[11px] bg-teal px-4 py-2.5 font-extrabold text-white disabled:opacity-60"
              :disabled="rowBusy === v.id"
              @click="makeActive(v)"
            >
              Tampilkan ini
            </button>
            <button
              type="button"
              class="rounded-[11px] border border-border-input px-4 py-2.5 font-bold text-danger hover:bg-danger-tint disabled:opacity-60"
              :disabled="rowBusy === v.id"
              @click="remove(v)"
            >
              Hapus
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
