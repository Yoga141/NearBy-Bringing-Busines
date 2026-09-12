<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAccountStore } from '@/stores/account'
import { useUiStore } from '@/stores/ui'
import { starsLabel } from '@/data/reviews'
import EditIcon from '@/components/shared/EditIcon.vue'
import SaveIcon from '@/components/shared/SaveIcon.vue'
import DeleteIcon from '@/components/shared/DeleteIcon.vue'

const account = useAccountStore()
const ui = useUiStore()
const router = useRouter()

const historyTab = ref<'komentar' | 'kunjungan'>('komentar')

onMounted(() => account.fetchCommentHistory())

const editingCid = ref<string | null>(null)
const editText = ref('')
const busyId = ref<string | null>(null)

function startEdit(cid: string, text: string) {
  editingCid.value = cid
  editText.value = text
}
function cancelEdit() {
  editingCid.value = null
}
async function saveEdit(cid: string) {
  if (!editText.value.trim()) return
  busyId.value = cid
  try {
    await account.updateComment(cid, editText.value.trim())
    editingCid.value = null
  } catch {
    alert('Gagal menyimpan perubahan komentar. Coba lagi.')
  } finally {
    busyId.value = null
  }
}
async function removeComment(cid: string, umkmName: string) {
  if (!confirm(`Hapus komentar untuk "${umkmName}"?`)) return
  busyId.value = cid
  try {
    await account.deleteComment(cid)
  } catch {
    alert('Gagal menghapus komentar. Coba lagi.')
  } finally {
    busyId.value = null
  }
}

function openUmkm(id: number) {
  ui.closeSettings()
  router.push({ name: 'detail', params: { id } })
}
</script>

<template>
  <div class="mb-4 flex gap-1.5 border-b border-[#E7DFCF]">
    <button
      type="button"
      class="border-b-[2.5px] px-1 pt-2 pb-2.5 text-[13.5px] font-extrabold"
      :class="historyTab === 'komentar' ? 'border-brand-blue text-brand-blue' : 'border-transparent text-text-muted'"
      @click="historyTab = 'komentar'"
    >
      Komentar
    </button>
    <button
      type="button"
      class="border-b-[2.5px] px-1 pt-2 pb-2.5 text-[13.5px] font-extrabold"
      :class="historyTab === 'kunjungan' ? 'border-brand-blue text-brand-blue' : 'border-transparent text-text-muted'"
      @click="historyTab = 'kunjungan'"
    >
      Kunjungan
    </button>
  </div>

  <template v-if="historyTab === 'komentar'">
    <div v-if="account.commentHistoryLoading && !account.commentHistory.length" class="rounded-[14px] border border-border-card bg-white px-5 py-10 text-center text-text-faint">
      Memuat komentar…
    </div>
    <div v-else-if="account.commentHistory.length === 0" class="rounded-[14px] border border-border-card bg-white px-5 py-10 text-center text-text-faint">
      <div class="text-3xl">💬</div>
      <div class="mt-2 text-[15px] font-extrabold text-brand-navy">Belum ada komentar</div>
      <p class="mt-1.5 text-[13px]">Komentar yang kamu tulis di UMKM akan muncul di sini.</p>
    </div>
    <div v-else class="flex flex-col gap-3">
      <div v-for="c in account.commentHistory" :key="c.id" class="rounded-[14px] border border-border-card bg-white px-[18px] py-4">
        <div class="mb-1.5 flex items-center justify-between gap-2.5">
          <button type="button" class="text-sm font-extrabold text-brand-blue hover:underline" @click="openUmkm(c.umkmId)">
            {{ c.umkmName }} ↗
          </button>
          <div class="text-[13px] font-bold whitespace-nowrap text-gold">{{ starsLabel(c.stars) }}</div>
        </div>
        <template v-if="editingCid !== c.id">
          <div class="text-[13px] leading-relaxed text-[#5B6470]">{{ c.text }}</div>
          <div class="mt-2.5 flex items-center gap-2">
            <div class="flex-1 text-xs font-semibold text-[#A69F8E]">{{ c.umkmCat }} · {{ c.date }}</div>
            <button
              type="button"
              class="flex items-center gap-1 rounded-[9px] bg-brand-blue-tint px-[13px] py-1.5 text-[12.5px] font-bold text-brand-blue disabled:opacity-50"
              :disabled="busyId === c.id"
              @click="startEdit(c.id, c.text)"
            >
              <EditIcon size="12px" /> Edit
            </button>
            <button
              type="button"
              class="flex items-center gap-1 rounded-[9px] border border-danger-border px-[13px] py-1.5 text-[12.5px] font-bold text-danger disabled:opacity-50"
              :disabled="busyId === c.id"
              @click="removeComment(c.id, c.umkmName ?? '')"
            >
              <DeleteIcon size="12px" /> Hapus
            </button>
          </div>
        </template>
        <template v-else>
          <textarea
            v-model="editText"
            class="mb-2.5 min-h-[78px] w-full resize-y rounded-[11px] border border-border-input px-3.5 py-2.5 text-brand-navy"
          />
          <div class="flex justify-end gap-2">
            <button type="button" class="rounded-[9px] border border-border-input px-3.5 py-2 text-[12.5px] font-bold text-text-muted" @click="cancelEdit">
              Batal
            </button>
            <button
              type="button"
              class="flex items-center gap-1 rounded-[9px] bg-brand-blue px-4 py-2 text-[12.5px] font-extrabold text-white disabled:opacity-60"
              :disabled="busyId === c.id"
              @click="saveEdit(c.id)"
            >
              <SaveIcon size="12px" /> Simpan
            </button>
          </div>
        </template>
      </div>
    </div>
  </template>

  <template v-else>
    <div class="rounded-[14px] border border-border-card bg-white px-5 py-10 text-center text-text-faint">
      <div class="text-3xl">🕒</div>
      <div class="mt-2 text-[15px] font-extrabold text-brand-navy">Riwayat kunjungan belum tersedia</div>
      <p class="mt-1.5 text-[13px]">Kami belum melacak UMKM yang kamu kunjungi. Cek tab "Komentar" untuk ulasan yang sudah kamu tulis.</p>
    </div>
  </template>
</template>
