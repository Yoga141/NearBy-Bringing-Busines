<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useDashboardStore } from '@/stores/dashboard'
import { ApiError } from '@/lib/api'
import BaseModal from '@/components/shared/BaseModal.vue'
import CloseIcon from '@/components/shared/CloseIcon.vue'

/**
 * Last stop before an account is erased for good. Shows exactly what will be
 * deleted (counted by the API) and only enables the button once the admin
 * types the account's email - a deliberate act, not a stray double-click.
 */
const ui = useUiStore()
const dashboard = useDashboardStore()

const user = computed(() => ui.modalItem as { id: number; name: string; email: string; deleted?: boolean } | null)

type Counts = Awaited<ReturnType<typeof dashboard.userPurgeSummary>>
const counts = ref<Counts | null>(null)
const loadError = ref('')
const typed = ref('')
const busy = ref(false)
const error = ref('')

const matches = computed(() => !!user.value && typed.value.trim().toLowerCase() === user.value.email.toLowerCase())

const lines = computed(() => {
  const c = counts.value
  if (!c) return []
  return [
    { label: 'UMKM milik akun ini (termasuk yang di sampah)', n: c.umkms },
    { label: 'Produk / menu', n: c.items },
    { label: 'Foto usaha (file ikut dihapus dari server)', n: c.photos },
    { label: 'Ulasan yang diterima UMKM-nya', n: c.reviewsReceived },
    { label: 'Ulasan yang ditulis di UMKM lain', n: c.reviewsWritten },
    { label: 'Favorit', n: c.favorites },
    { label: 'Pertanyaan & laporan masalah', n: c.questions + c.problemReports },
  ]
})

onMounted(async () => {
  if (!user.value) return
  try {
    counts.value = await dashboard.userPurgeSummary(user.value.id)
  } catch (e) {
    loadError.value = e instanceof ApiError ? e.firstError : 'Gagal memuat ringkasan data akun.'
  }
})

async function confirmPurge() {
  if (!user.value || !matches.value || busy.value) return
  busy.value = true
  error.value = ''
  try {
    const message = await dashboard.userPurge(user.value.id, typed.value.trim())
    ui.closeModal()
    alert(message)
  } catch (e) {
    error.value = e instanceof ApiError ? e.firstError : 'Gagal menghapus akun secara permanen.'
  } finally {
    busy.value = false
  }
}

function close() {
  if (!busy.value) ui.closeModal()
}
</script>

<template>
  <BaseModal max-width="max-w-[460px]" @close="close">
    <div role="alertdialog" aria-labelledby="purge-title" aria-describedby="purge-desc">
      <div class="flex items-center justify-between border-b border-border-divider px-6 py-[19px]">
        <div id="purge-title" class="text-lg font-extrabold text-danger">Hapus permanen akun</div>
        <button type="button" class="text-text-faint" aria-label="Tutup" :disabled="busy" @click="close"><CloseIcon size="20px" /></button>
      </div>

      <div class="px-6 py-[20px]">
        <div class="mb-4 rounded-xl border border-danger-border bg-[#FBEEEA] px-4 py-3 text-[13px] font-semibold text-danger">
          Tindakan ini <strong>tidak bisa dibatalkan</strong>. Data tidak masuk sampah dan tidak bisa dipulihkan.
        </div>

        <p id="purge-desc" class="mb-3 text-[14px] text-brand-navy">
          Akun <strong>{{ user?.name }}</strong> ({{ user?.email }}) akan dihapus beserta:
        </p>

        <div v-if="loadError" class="mb-4 text-[13px] font-semibold text-danger">{{ loadError }}</div>
        <div v-else-if="!counts" class="mb-4 text-[13px] text-text-muted">Menghitung data terkait…</div>
        <ul v-else class="mb-4 divide-y divide-[#F2ECDF] rounded-xl border border-border-card text-[13px]">
          <li v-for="line in lines" :key="line.label" class="flex items-center justify-between gap-3 px-3.5 py-2">
            <span class="text-text-muted">{{ line.label }}</span>
            <span class="font-extrabold" :class="line.n ? 'text-danger' : 'text-text-faint'">{{ line.n }}</span>
          </li>
        </ul>

        <label for="purge-confirm" class="mb-1.5 block text-[13px] font-bold text-brand-navy">
          Ketik <span class="select-all font-extrabold">{{ user?.email }}</span> untuk mengonfirmasi
        </label>
        <input
          id="purge-confirm"
          v-model="typed"
          type="email"
          autocomplete="off"
          spellcheck="false"
          :placeholder="user?.email"
          class="w-full rounded-xl border border-border-input bg-white px-3.5 py-2.5 font-medium text-brand-navy placeholder:text-text-faint"
          @keydown.enter.prevent="confirmPurge"
        />
        <p v-if="error" class="mt-2 text-[13px] font-semibold text-danger" role="alert">{{ error }}</p>

        <div class="mt-5 flex justify-end gap-2.5">
          <button type="button" class="rounded-xl border border-[#E7E0D2] px-4 py-2.5 text-sm font-bold text-brand-navy" :disabled="busy" @click="close">
            Batal
          </button>
          <button
            type="button"
            class="rounded-xl bg-danger px-4 py-2.5 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!matches || busy || !!loadError"
            @click="confirmPurge"
          >
            {{ busy ? 'Menghapus…' : 'Hapus permanen' }}
          </button>
        </div>
      </div>
    </div>
  </BaseModal>
</template>
