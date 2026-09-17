<script setup lang="ts">
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useReviewsStore } from '@/stores/reviews'
import StarIcon from '@/components/shared/StarIcon.vue'
import CloseIcon from '@/components/shared/CloseIcon.vue'
import PlusIcon from '@/components/shared/PlusIcon.vue'

const props = defineProps<{ umkmId: number }>()

const auth = useAuthStore()
const reviews = useReviewsStore()

const stars = ref(5)
const text = ref('')
const media = ref<{ url: string; file: File }[]>([])

function onPickMedia(e: Event) {
  const files = (e.target as HTMLInputElement).files
  if (!files) return
  for (const file of Array.from(files)) {
    media.value.push({ url: URL.createObjectURL(file), file })
  }
  ;(e.target as HTMLInputElement).value = ''
}

function removeMedia(i: number) {
  media.value.splice(i, 1)
}

async function submit() {
  if (!text.value.trim() || !auth.user) return
  const ok = await reviews.addReview(props.umkmId, { stars: stars.value, text: text.value.trim() })
  if (ok) {
    stars.value = 5
    text.value = ''
    media.value = []
  }
}
</script>

<template>
  <div class="mt-[22px] rounded-2xl border border-border-card bg-white p-5">
    <template v-if="auth.isAuthed">
      <div class="mb-3 font-extrabold">Beri rating &amp; komentar</div>
      <div class="mb-3 flex gap-1">
        <StarIcon
          v-for="n in 5"
          :key="n"
          size="28px"
          class="cursor-pointer"
          :filled="n <= stars"
          :style="{ color: n <= stars ? '#E7A83A' : '#E2DBCB' }"
          @click="stars = n"
        />
      </div>
      <textarea
        v-model="text"
        rows="3"
        placeholder="Ceritakan pengalamanmu…"
        class="mb-3 w-full rounded-xl border border-[#E7E0D2] p-3"
      />
      <div class="mb-3 flex flex-wrap gap-2.5">
        <div v-for="(m, i) in media" :key="m.url" class="relative h-[74px] w-[74px]">
          <img :src="m.url" class="h-full w-full rounded-xl object-cover" />
          <button
            type="button"
            class="absolute -top-1.5 -right-1.5 flex h-[22px] w-[22px] items-center justify-center rounded-full border border-white bg-brand-navy text-xs text-white"
            @click="removeMedia(i)"
          >
            <CloseIcon size="12px" />
          </button>
        </div>
        <label
          class="flex h-[74px] w-[74px] cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-[#D6CFC0] text-[11px] font-semibold text-text-faint hover:border-brand-blue hover:text-brand-blue"
          style="background: repeating-linear-gradient(135deg, #f6f1e8 0 9px, #fbf8f2 9px 18px)"
        >
          <PlusIcon size="18px" />
          Media
          <input type="file" accept="image/*,video/*" multiple class="hidden" @change="onPickMedia" />
        </label>
      </div>
      <p v-if="reviews.error" class="mb-3 text-[13px] font-semibold text-danger">{{ reviews.error }}</p>
      <button
        type="button"
        class="rounded-[11px] bg-brand-blue px-5 py-2.5 font-bold text-white disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="reviews.submitting"
        @click="submit"
      >
        {{ reviews.submitting ? 'Mengirim…' : 'Kirim ulasan' }}
      </button>
    </template>
    <div v-else class="flex flex-wrap items-center justify-between gap-3">
      <div class="text-text-secondary">Masuk untuk memberi rating dan komentar.</div>
      <RouterLink :to="{ name: 'login' }" class="rounded-[11px] bg-brand-navy px-5 py-2.5 font-bold text-white">
        Masuk sekarang
      </RouterLink>
    </div>
  </div>
</template>
