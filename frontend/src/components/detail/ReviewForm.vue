<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useReviewsStore } from '@/stores/reviews'
import { ApiError } from '@/lib/api'
import StarIcon from '@/components/shared/StarIcon.vue'

/**
 * The signed-in visitor's single review of this UMKM.
 *
 * No review yet → "Beri rating & komentar". Already reviewed → the form edits
 * that review instead (the API refuses a second one anyway). The owner of the
 * UMKM gets neither.
 */
const props = defineProps<{ umkmId: number; ownerId: number | null }>()
const emit = defineEmits<{ changed: [] }>()

const auth = useAuthStore()
const reviews = useReviewsStore()

const myReview = computed(() =>
  auth.user ? reviews.reviewsFor(props.umkmId).find((r) => r.userId === auth.user?.id) ?? null : null,
)
const isOwner = computed(() => !!auth.user && props.ownerId !== null && props.ownerId === auth.user.id)

const editing = ref(false)
const stars = ref(5)
const text = ref('')
const localError = ref('')
const saving = ref(false)
const saved = ref('')

// Seed the form from the existing review whenever the user starts editing.
watch(editing, (on) => {
  if (on && myReview.value) {
    stars.value = myReview.value.stars
    text.value = myReview.value.text
  }
})

function validate(): boolean {
  localError.value = ''
  if (text.value.trim().length < 3) {
    localError.value = 'Komentar minimal 3 karakter.'
    return false
  }
  return true
}

async function submit() {
  saved.value = ''
  if (!auth.user || !validate()) return
  const ok = await reviews.addReview(props.umkmId, { stars: stars.value, text: text.value.trim() })
  if (ok) {
    stars.value = 5
    text.value = ''
    saved.value = 'Ulasan terkirim. Terima kasih!'
    emit('changed')
  } else if (myReview.value) {
    // 409: already reviewed (e.g. from another tab) - offer to edit that one.
    editing.value = true
  }
}

async function saveEdit() {
  saved.value = ''
  if (!myReview.value || !validate()) return
  saving.value = true
  try {
    await reviews.updateReview(props.umkmId, myReview.value.id, { stars: stars.value, text: text.value.trim() })
    editing.value = false
    saved.value = 'Ulasanmu diperbarui.'
    emit('changed')
  } catch (e) {
    localError.value = e instanceof ApiError ? e.firstError : 'Gagal menyimpan ulasan. Coba lagi.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="mt-[22px] rounded-2xl border border-border-card bg-white p-5">
    <template v-if="!auth.isAuthed">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="text-text-secondary">Masuk untuk memberi rating dan komentar.</div>
        <RouterLink :to="{ name: 'login' }" class="rounded-[11px] bg-brand-navy px-5 py-2.5 font-bold text-white">
          Masuk sekarang
        </RouterLink>
      </div>
    </template>

    <div v-else-if="isOwner" class="text-text-secondary">
      Ini UMKM milikmu. Kamu bisa membalas ulasan pengunjung dari dashboard.
    </div>

    <template v-else-if="myReview && !editing">
      <div class="mb-1 font-extrabold">Kamu sudah memberi ulasan</div>
      <p class="mb-3 text-[14px] text-text-secondary">
        Satu akun hanya bisa memberi satu ulasan per UMKM. Kamu bisa mengubahnya kapan saja.
      </p>
      <p v-if="saved" class="mb-3 text-[13px] font-semibold text-teal-deep">{{ saved }}</p>
      <button type="button" class="rounded-[11px] bg-brand-blue px-5 py-2.5 font-bold text-white" @click="editing = true">
        Edit ulasanku
      </button>
    </template>

    <template v-else>
      <div class="mb-3 font-extrabold">{{ editing ? 'Edit ulasanmu' : 'Beri rating & komentar' }}</div>
      <div class="mb-3 flex gap-1" role="radiogroup" aria-label="Jumlah bintang">
        <button
          v-for="n in 5"
          :key="n"
          type="button"
          role="radio"
          :aria-checked="n === stars"
          :aria-label="`${n} bintang`"
          @click="stars = n"
        >
          <StarIcon size="28px" :filled="n <= stars" :style="{ color: n <= stars ? '#E7A83A' : '#E2DBCB' }" />
        </button>
      </div>
      <textarea
        v-model="text"
        rows="3"
        maxlength="2000"
        placeholder="Ceritakan pengalamanmu…"
        class="mb-3 w-full rounded-xl border border-[#E7E0D2] p-3"
      />
      <p v-if="localError || reviews.error" class="mb-3 text-[13px] font-semibold text-danger">
        {{ localError || reviews.error }}
      </p>
      <p v-if="saved && !editing" class="mb-3 text-[13px] font-semibold text-teal-deep">{{ saved }}</p>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="editing"
          type="button"
          class="rounded-[11px] bg-brand-blue px-5 py-2.5 font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="saving"
          @click="saveEdit"
        >
          {{ saving ? 'Menyimpan…' : 'Simpan perubahan' }}
        </button>
        <button
          v-if="editing"
          type="button"
          class="rounded-[11px] border border-[#E7E0D2] px-5 py-2.5 font-bold text-brand-navy"
          @click="editing = false"
        >
          Batal
        </button>
        <button
          v-else
          type="button"
          class="rounded-[11px] bg-brand-blue px-5 py-2.5 font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="reviews.submitting"
          @click="submit"
        >
          {{ reviews.submitting ? 'Mengirim…' : 'Kirim ulasan' }}
        </button>
      </div>
    </template>
  </div>
</template>
