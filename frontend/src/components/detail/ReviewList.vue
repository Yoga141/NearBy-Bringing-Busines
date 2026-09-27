<script setup lang="ts">
import { ref } from 'vue'
import { starsCount } from '@/data/reviews'
import { useAuthStore } from '@/stores/auth'
import { useReviewsStore } from '@/stores/reviews'
import { ApiError } from '@/lib/api'
import StarRating from '@/components/shared/StarRating.vue'
import StarIcon from '@/components/shared/StarIcon.vue'
import DeleteIcon from '@/components/shared/DeleteIcon.vue'
import EditIcon from '@/components/shared/EditIcon.vue'
import SaveIcon from '@/components/shared/SaveIcon.vue'
import type { Review } from '@/types'

defineProps<{ reviews: Review[] }>()
const emit = defineEmits<{ changed: [] }>()

const auth = useAuthStore()
const reviewsStore = useReviewsStore()

const editingId = ref<string | null>(null)
const editStars = ref(5)
const editText = ref('')
const busyId = ref<string | null>(null)
const editError = ref('')

function isMine(rv: Review) {
  return auth.isAuthed && rv.userId !== null && rv.userId === auth.user?.id
}

/** Admins moderate: they may delete any review, but only edit their own. */
function canDelete(rv: Review) {
  return isMine(rv) || auth.isAdmin
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
  editError.value = ''
  if (editText.value.trim().length < 3) {
    editError.value = 'Komentar minimal 3 karakter.'
    return
  }
  busyId.value = rv.id
  try {
    await reviewsStore.updateReview(rv.umkmId, rv.id, { stars: editStars.value, text: editText.value.trim() })
    editingId.value = null
    emit('changed')
  } catch (e) {
    editError.value = e instanceof ApiError ? e.firstError : 'Gagal menyimpan perubahan ulasan. Coba lagi.'
  } finally {
    busyId.value = null
  }
}

async function removeReview(rv: Review) {
  if (!confirm(isMine(rv) ? 'Hapus ulasanmu?' : `Hapus ulasan dari ${rv.name}?`)) return
  busyId.value = rv.id
  try {
    await reviewsStore.deleteReview(rv.umkmId, rv.id)
    emit('changed')
  } catch (e) {
    alert(e instanceof ApiError ? e.firstError : 'Gagal menghapus ulasan. Coba lagi.')
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
        <div v-if="canDelete(rv) && editingId !== rv.id" class="ml-auto flex gap-3">
          <button
            v-if="isMine(rv)"
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
          maxlength="2000"
          class="mt-2.5 w-full rounded-xl border border-[#E7E0D2] p-3 text-[14.5px]"
        />
        <p v-if="editError" class="mt-1.5 text-[13px] font-semibold text-danger">{{ editError }}</p>
        <div class="mt-2 flex gap-2">
          <button type="button" class="flex items-center gap-1.5 rounded-[10px] bg-brand-blue px-4 py-2 text-[13px] font-bold text-white" @click="saveEdit(rv)">
            <SaveIcon size="13px" /> Simpan
          </button>
          <button type="button" class="rounded-[10px] border border-[#E7E0D2] px-4 py-2 text-[13px] font-bold text-brand-navy" @click="cancelEdit">
            Batal
          </button>
        </div>
      </template>
      <p v-else class="mt-1.5 text-[14.5px] leading-[1.55] whitespace-pre-line text-text-secondary">{{ rv.text }}</p>
      <div v-if="rv.reply && editingId !== rv.id" class="mt-2 rounded-xl bg-[#F6F2EA] px-3.5 py-2.5 text-[13.5px] text-text-secondary">
        <b class="text-brand-navy">Balasan pemilik:</b> {{ rv.reply }}
      </div>
    </div>
  </div>
</template>
