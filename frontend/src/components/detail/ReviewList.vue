<script setup lang="ts">
import { ref } from 'vue'
import { starsCount } from '@/data/reviews'
import { useAuthStore } from '@/stores/auth'
import { useReviewsStore } from '@/stores/reviews'
import StarRating from '@/components/shared/StarRating.vue'
import StarIcon from '@/components/shared/StarIcon.vue'
import DeleteIcon from '@/components/shared/DeleteIcon.vue'
import EditIcon from '@/components/shared/EditIcon.vue'
import SaveIcon from '@/components/shared/SaveIcon.vue'
import type { Review } from '@/types'

defineProps<{ reviews: Review[] }>()

const auth = useAuthStore()
const reviewsStore = useReviewsStore()

const editingId = ref<string | null>(null)
const editStars = ref(5)
const editText = ref('')
const busyId = ref<string | null>(null)

function isMine(rv: Review) {
  return auth.isAuthed && rv.userId !== null && rv.userId === auth.user?.id
}

function startEdit(rv: Review) {
  editingId.value = rv.id
  editStars.value = rv.stars
  editText.value = rv.text
}

function cancelEdit() {
  editingId.value = null
}

async function saveEdit(rv: Review) {
  if (!editText.value.trim()) return
  busyId.value = rv.id
  try {
    await reviewsStore.updateReview(rv.umkmId, rv.id, { stars: editStars.value, text: editText.value.trim() })
    editingId.value = null
  } catch {
    alert('Gagal menyimpan perubahan ulasan. Coba lagi.')
  } finally {
    busyId.value = null
  }
}

async function removeReview(rv: Review) {
  if (!confirm('Hapus ulasan ini?')) return
  busyId.value = rv.id
  try {
    await reviewsStore.deleteReview(rv.umkmId, rv.id)
  } catch {
    alert('Gagal menghapus ulasan. Coba lagi.')
  } finally {
    busyId.value = null
  }
}
</script>

<template>
  <div
    v-for="rv in reviews"
    :key="rv.id"
    class="flex gap-[13px] border-t border-[#F0EADD] py-4 first:border-t-0"
  >
    <div class="flex h-[42px] w-[42px] flex-none items-center justify-center rounded-full bg-brand-blue-tint font-extrabold text-brand-blue">
      {{ rv.initial }}
    </div>
    <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-2.5">
        <div class="font-bold">{{ rv.name }}</div>
        <div class="flex items-center gap-0.5 text-gold">
          <StarIcon v-for="n in 5" :key="n" :filled="n <= starsCount(rv.stars)" size="13px" />
        </div>
        <div class="text-[12.5px] font-semibold text-text-faint-3">{{ rv.date }}</div>
        <div v-if="isMine(rv) && editingId !== rv.id" class="ml-auto flex gap-3">
          <button
            type="button"
            class="flex items-center gap-1 text-[12px] font-bold text-brand-navy outline-none hover:underline disabled:opacity-50"
            :disabled="busyId === rv.id"
            @click="startEdit(rv)"
          >
            <EditIcon size="13px" /> Edit
          </button>
          <button
            type="button"
            class="flex items-center gap-1 text-[12px] font-bold text-danger outline-none hover:underline disabled:opacity-50"
            :disabled="busyId === rv.id"
            @click="removeReview(rv)"
          >
            <DeleteIcon size="13px" /> Hapus
          </button>
        </div>
      </div>

      <template v-if="editingId === rv.id">
        <div class="mt-2.5 flex gap-1">
          <StarRating v-model="editStars" interactive />
        </div>
        <textarea
          v-model="editText"
          rows="3"
          class="mt-2.5 w-full rounded-xl border border-[#E7E0D2] p-3 text-[14.5px]"
        />
        <div class="mt-2 flex gap-2">
          <button type="button" class="flex items-center gap-1.5 rounded-[10px] bg-brand-blue px-4 py-2 text-[13px] font-bold text-white" @click="saveEdit(rv)">
            <SaveIcon size="13px" /> Simpan
          </button>
          <button type="button" class="rounded-[10px] border border-[#E7E0D2] px-4 py-2 text-[13px] font-bold text-brand-navy" @click="cancelEdit">
            Batal
          </button>
        </div>
      </template>
      <p v-else class="mt-1.5 text-[14.5px] leading-[1.55] text-text-secondary">{{ rv.text }}</p>
    </div>
  </div>
</template>
