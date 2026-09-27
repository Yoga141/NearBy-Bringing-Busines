<script setup lang="ts">
import { nextTick, ref, watch } from 'vue'
import { useAssistantStore } from '@/stores/assistant'
import UmkmThumb from '@/components/shared/UmkmThumb.vue'
import CloseIcon from '@/components/shared/CloseIcon.vue'
import ChatBubbleIcon from '@/components/shared/ChatBubbleIcon.vue'
import StarIcon from '@/components/shared/StarIcon.vue'

/**
 * Floating "Asisten NearBy" chat. Every answer is built from the database
 * search on the server; the cards under an answer are those same rows.
 */
const assistant = useAssistantStore()

const draft = ref('')
const list = ref<HTMLElement | null>(null)
const input = ref<HTMLInputElement | null>(null)

const SUGGESTIONS = ['Carikan UMKM makanan di Balikpapan', 'Penginapan dekat bandara', 'Oleh-oleh yang murah']

async function scrollDown() {
  await nextTick()
  list.value?.scrollTo({ top: list.value.scrollHeight })
}

watch(() => assistant.messages.length, scrollDown)
watch(
  () => assistant.open,
  async (open) => {
    if (!open) return
    await scrollDown()
    input.value?.focus()
  },
)

async function send(text = draft.value) {
  if (!text.trim() || assistant.sending) return
  draft.value = ''
  await assistant.ask(text)
}
</script>

<template>
  <div class="fixed bottom-4 left-4 z-[74] flex flex-col items-start gap-3 mobile:bottom-[22px] mobile:left-[22px]">
    <section
      v-if="assistant.open"
      class="flex h-[min(560px,calc(100vh-120px))] w-[370px] max-w-[calc(100vw-32px)] flex-col overflow-hidden rounded-[18px] border border-border-card bg-white shadow-[0_22px_55px_rgba(9,24,40,.28)]"
      aria-label="Asisten NearBy"
    >
      <header class="flex flex-none items-center justify-between bg-brand-navy px-[18px] py-3.5 text-white">
        <div>
          <div class="text-[15.5px] font-extrabold">Asisten NearBy</div>
          <div class="mt-px text-xs text-[#AFC3DC]">Mencari dari data UMKM NearBy</div>
        </div>
        <div class="flex items-center gap-3">
          <button
            v-if="assistant.messages.length"
            type="button"
            class="text-xs font-bold text-[#AFC3DC] hover:text-white"
            @click="assistant.reset()"
          >
            Mulai baru
          </button>
          <button type="button" aria-label="Tutup asisten" class="text-[#AFC3DC]" @click="assistant.open = false"><CloseIcon size="20px" /></button>
        </div>
      </header>

      <div ref="list" class="flex-1 space-y-3 overflow-y-auto bg-cream px-3.5 py-3.5" aria-live="polite">
        <div v-if="!assistant.messages.length" class="rounded-2xl bg-white p-3.5 text-[13.5px] leading-relaxed text-text-secondary">
          Halo! Saya bisa mencarikan UMKM di Balikpapan. Tanya dengan bahasa sehari-hari, lalu persempit dengan
          "yang murah", "yang dekat kampus", atau "detail nomor 1".
          <div class="mt-3 flex flex-wrap gap-2">
            <button
              v-for="s in SUGGESTIONS"
              :key="s"
              type="button"
              class="rounded-full border border-border-input bg-cream px-3 py-1.5 text-[12.5px] font-bold text-brand-navy hover:border-brand-blue"
              @click="send(s)"
            >
              {{ s }}
            </button>
          </div>
        </div>

        <div v-for="(m, i) in assistant.messages" :key="i" :class="m.role === 'user' ? 'flex justify-end' : ''">
          <div
            class="max-w-[88%] rounded-2xl px-3.5 py-2.5 text-[13.5px] leading-relaxed whitespace-pre-line"
            :class="
              m.role === 'user'
                ? 'bg-brand-blue text-white'
                : m.error
                  ? 'border border-danger-border bg-white text-danger'
                  : 'bg-white text-text-secondary'
            "
          >
            {{ m.text }}
          </div>
          <div v-if="m.results?.length" class="mt-2 flex flex-col gap-2">
            <RouterLink
              v-for="u in m.results"
              :key="u.id"
              :to="{ name: 'detail', params: { id: u.id } }"
              class="flex items-center gap-2.5 rounded-xl border border-border-card bg-white p-2 hover:border-brand-blue"
            >
              <div class="h-11 w-11 flex-none overflow-hidden rounded-lg">
                <UmkmThumb :src="u.coverUrl" :alt="`Foto ${u.name}`" rounded="rounded-lg" label=" " />
              </div>
              <div class="min-w-0 flex-1">
                <div class="truncate text-[13.5px] font-extrabold text-brand-navy">{{ u.name }}</div>
                <div class="truncate text-[12px] text-text-faint">{{ u.cat }} · {{ u.loc }}<template v-if="u.priceLabel"> · {{ u.priceLabel }}</template></div>
              </div>
              <div v-if="u.reviews" class="flex flex-none items-center gap-0.5 text-[12px] font-extrabold text-gold">
                <StarIcon size="11px" /> {{ u.rating }}
              </div>
            </RouterLink>
          </div>
        </div>

        <div v-if="assistant.sending" class="w-fit rounded-2xl bg-white px-3.5 py-2.5 text-[13.5px] text-text-faint">Mencari…</div>
      </div>

      <form class="flex flex-none gap-2 border-t border-border-divider bg-white p-2.5" @submit.prevent="send()">
        <input
          ref="input"
          v-model="draft"
          maxlength="500"
          placeholder="Tulis pertanyaanmu…"
          aria-label="Pertanyaan untuk asisten"
          class="min-w-0 flex-1 rounded-xl border border-border-input bg-white px-3.5 py-2.5 text-[14px]"
        />
        <button
          type="submit"
          class="rounded-xl bg-brand-blue px-4 py-2.5 text-[14px] font-extrabold text-white disabled:opacity-60"
          :disabled="assistant.sending || !draft.trim()"
        >
          Kirim
        </button>
      </form>
    </section>

    <button
      type="button"
      class="flex items-center gap-2 rounded-full bg-brand-navy px-4 py-3 font-extrabold text-white shadow-[0_12px_30px_rgba(9,24,40,.3)]"
      :aria-expanded="assistant.open"
      @click="assistant.open = !assistant.open"
    >
      <ChatBubbleIcon size="18px" />
      <span class="text-[14px]">{{ assistant.open ? 'Tutup' : 'Tanya Asisten' }}</span>
    </button>
  </div>
</template>
