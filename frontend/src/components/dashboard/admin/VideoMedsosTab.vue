<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useVideoStore } from '@/stores/videos'
import { ApiError } from '@/lib/api'
import ToggleSwitch from '@/components/shared/ToggleSwitch.vue'
import ClapperboardIcon from '@/components/shared/ClapperboardIcon.vue'
import PlusIcon from '@/components/shared/PlusIcon.vue'
import type { SocialVideo, VideoPlatform } from '@/types'

const store = useVideoStore()

interface RowDraft {
  platform: VideoPlatform
  title: string
  url: string
  active: boolean
}

/** Per-row edit buffer, keyed by video id, so a failed save doesn't wipe the form. */
const drafts = reactive<Record<number, RowDraft>>({})
const errors = reactive<Record<number, string>>({})
const saving = reactive<Record<number, boolean>>({})
const savedId = ref<number | null>(null)

// Seed a buffer for every row that doesn't have one yet (and drop buffers for
// deleted rows). Done in a watcher rather than on access from the template, so
// the render pass never mutates reactive state.
watch(
  () => store.adminVideos,
  (rows) => {
    const ids = new Set(rows.map((r) => r.id))
    for (const id of Object.keys(drafts).map(Number)) {
      if (!ids.has(id)) delete drafts[id]
    }
    for (const v of rows) {
      drafts[v.id] ??= { platform: v.platform, title: v.title, url: v.url ?? '', active: v.active }
    }
  },
  { immediate: true, deep: false },
)

async function save(v: SocialVideo) {
  const d = drafts[v.id]
  if (!d.title.trim()) {
    errors[v.id] = 'Judul tidak boleh kosong.'
    return
  }
  errors[v.id] = ''
  saving[v.id] = true
  try {
    await store.updateVideo(v.id, {
      platform: d.platform,
      title: d.title.trim(),
      url: d.url.trim() || null,
      active: d.active,
    })
    savedId.value = v.id
    setTimeout(() => {
      if (savedId.value === v.id) savedId.value = null
    }, 2400)
  } catch (e) {
    errors[v.id] = e instanceof ApiError ? e.firstError : 'Gagal menyimpan. Coba lagi.'
  } finally {
    saving[v.id] = false
  }
}

async function remove(v: SocialVideo) {
  if (!confirm(`Hapus slot video "${v.title}"?`)) return
  try {
    await store.deleteVideo(v.id)
    delete drafts[v.id]
  } catch {
    alert('Gagal menghapus slot. Coba lagi.')
  }
}

const adding = ref(false)
async function addSlot() {
  adding.value = true
  try {
    await store.createVideo({ platform: 'youtube', title: 'Judul video baru', url: null })
  } catch (e) {
    alert(e instanceof ApiError ? e.firstError : 'Gagal menambah slot. Coba lagi.')
  } finally {
    adding.value = false
  }
}

onMounted(() => store.fetchAdminVideos())
</script>

<template>
  <div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Video Medsos</h1>
      <p class="mt-[7px] text-text-muted">
        Tempel tautan Instagram atau YouTube di sini - kartu di beranda langsung ikut berubah.
      </p>
    </div>
    <button
      type="button"
      class="flex items-center gap-1.5 rounded-[11px] bg-brand-navy px-[18px] py-2.5 font-extrabold text-white disabled:opacity-60"
      :disabled="adding"
      @click="addSlot"
    >
      <PlusIcon size="13px" /> Tambah slot
    </button>
  </div>

  <div v-if="store.adminLoading && !store.adminVideos.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center text-text-faint">
    Memuat…
  </div>

  <div v-else-if="!store.adminVideos.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center">
    <ClapperboardIcon size="42px" class="mx-auto text-text-faint" />
    <div class="mt-3 text-[19px] font-extrabold text-brand-navy">Belum ada slot video</div>
    <p class="mx-auto mt-2 max-w-[420px] text-[14.5px] leading-relaxed text-text-muted">
      Tambah slot untuk menampilkan cuplikan medsos NearBy di halaman beranda.
    </p>
  </div>

  <div v-else class="flex flex-col gap-3.5">
    <div
      v-for="v in store.adminVideos"
      :key="v.id"
      class="rounded-[18px] border border-border-card bg-white p-5 shadow-[0_4px_16px_rgba(19,50,77,.04)]"
    >
      <div class="flex flex-wrap items-center gap-2.5">
        <span class="rounded-full bg-brand-blue-tint px-[11px] py-1 text-[11.5px] font-bold text-brand-blue">
          Slot #{{ v.sortOrder }}
        </span>
        <span
          class="rounded-full px-[11px] py-1 text-[11.5px] font-bold"
          :style="v.url ? { background: '#E3EFED', color: '#2E7D6E' } : { background: '#EEEADF', color: '#8A8578' }"
        >
          {{ v.url ? 'Ada video' : 'Belum ada video' }}
        </span>
        <div class="ml-auto flex items-center gap-2.5">
          <span class="text-[12.5px] font-bold text-text-faint">Tampilkan</span>
          <ToggleSwitch v-model="drafts[v.id].active" />
        </div>
      </div>

      <div class="mt-4 grid grid-cols-1 gap-3.5 tablet:grid-cols-[170px_1fr]">
        <div>
          <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Platform</label>
          <select
            v-model="drafts[v.id].platform"
            class="w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 font-semibold text-brand-navy"
          >
            <option value="youtube">YouTube</option>
            <option value="instagram">Instagram</option>
          </select>
        </div>
        <div>
          <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Judul kartu</label>
          <input
            v-model="drafts[v.id].title"
            placeholder="Mis. Jelajah Oleh-Oleh Khas Balikpapan"
            class="w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy"
          />
        </div>
      </div>

      <div class="mt-3.5">
        <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Tautan video</label>
        <input
          v-model="drafts[v.id].url"
          placeholder="Tempel tautan YouTube / Instagram - kosongkan untuk mengosongkan kartu"
          class="w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy"
        />
        <p class="mt-1.5 text-[12px] leading-relaxed text-text-faint">
          YouTube: tautan video, <span class="font-mono">youtu.be</span>, atau Shorts. Instagram: permalink post atau reel.
        </p>
      </div>

      <div class="mt-3.5 flex flex-wrap items-center justify-between gap-3 border-t border-border-divider-2 pt-3.5">
        <div class="min-h-[20px] text-[13px] font-semibold">
          <span v-if="errors[v.id]" class="text-danger">{{ errors[v.id] }}</span>
          <span v-else-if="savedId === v.id" class="text-teal-deep">Perubahan tersimpan.</span>
        </div>
        <div class="flex gap-2">
          <button
            type="button"
            class="rounded-[11px] border border-border-input px-4 py-2.5 font-bold text-danger hover:bg-danger-tint"
            @click="remove(v)"
          >
            Hapus slot
          </button>
          <button
            type="button"
            class="rounded-[11px] bg-brand-blue px-5 py-2.5 font-extrabold text-white disabled:opacity-60"
            :disabled="saving[v.id]"
            @click="save(v)"
          >
            {{ saving[v.id] ? 'Menyimpan…' : 'Simpan' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
