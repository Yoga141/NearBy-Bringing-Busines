<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import { ApiError } from '@/lib/api'
import ChatBubbleIcon from '@/components/shared/ChatBubbleIcon.vue'
import type { QuestionStatus } from '@/types'

const dashboard = useDashboardStore()

const STATUS_OPTIONS: QuestionStatus[] = ['Baru', 'Dijawab', 'Ditutup']

/** Per-question answer buffer, seeded in a watcher so render never mutates state. */
const drafts = reactive<Record<string, string>>({})
const saving = reactive<Record<string, boolean>>({})
const errors = reactive<Record<string, string>>({})
const openId = ref<string | null>(null)

watch(
  () => dashboard.questions,
  (rows) => {
    for (const q of rows) drafts[q.id] ??= q.answer ?? ''
  },
  { immediate: true },
)

async function save(id: string) {
  const text = (drafts[id] ?? '').trim()
  if (!text) {
    errors[id] = 'Jawaban tidak boleh kosong.'
    return
  }
  errors[id] = ''
  saving[id] = true
  try {
    await dashboard.answerQuestion(id, text)
    openId.value = null
  } catch (e) {
    errors[id] = e instanceof ApiError ? e.firstError : 'Gagal menyimpan jawaban. Coba lagi.'
  } finally {
    saving[id] = false
  }
}
</script>

<template>
  <div class="mb-[22px] flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">Pertanyaan</h1>
      <p class="mt-[7px] text-text-muted">
        Pertanyaan yang dikirim pengguna lewat "Pusat Bantuan → Bertanya".
        <span v-if="dashboard.newQuestionCount" class="font-bold text-gold">
          {{ dashboard.newQuestionCount }} baru
        </span>
      </p>
    </div>
  </div>

  <div v-if="!dashboard.questions.length" class="rounded-[18px] border border-border-card bg-white px-6 py-16 text-center">
    <ChatBubbleIcon size="42px" class="mx-auto text-text-faint" />
    <div class="mt-3 text-[19px] font-extrabold text-brand-navy">Belum ada pertanyaan</div>
    <p class="mx-auto mt-2 max-w-[440px] text-[14.5px] leading-relaxed text-text-muted">
      Pertanyaan dari pengguna akan muncul di sini beserta kontak yang mereka isi.
    </p>
  </div>

  <div v-else class="flex flex-col gap-3.5">
    <div
      v-for="q in dashboard.questions"
      :key="q.id"
      class="rounded-[18px] border border-border-card bg-white p-5 shadow-[0_4px_16px_rgba(19,50,77,.04)]"
    >
      <div class="flex flex-wrap items-center gap-2.5">
        <span class="rounded-full px-[11px] py-1 text-[11.5px] font-bold" :style="{ background: q.statusBg, color: q.statusColor }">
          {{ q.status }}
        </span>
        <span class="ml-auto text-[12.5px] font-semibold text-text-faint">{{ q.when }}</span>
      </div>

      <p class="mt-3 text-[14.5px] leading-relaxed text-text-secondary">{{ q.text }}</p>

      <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px]">
        <span class="font-semibold text-text-faint">
          Dari <span class="font-bold text-brand-navy">{{ q.name }}</span>
        </span>
        <span v-if="q.contact" class="font-semibold text-text-faint">
          Kontak <span class="font-bold text-brand-navy">{{ q.contact }}</span>
        </span>
        <span v-else class="font-semibold text-gold">Tidak mencantumkan kontak</span>
      </div>

      <div v-if="q.answer && openId !== q.id" class="mt-3.5 rounded-[12px] bg-teal-tint px-3.5 py-3">
        <div class="text-[11.5px] font-extrabold tracking-[.06em] text-teal-deep uppercase">Jawaban admin</div>
        <p class="mt-1 text-[13.5px] leading-relaxed text-teal-deep">{{ q.answer }}</p>
      </div>

      <div v-if="openId === q.id" class="mt-3.5">
        <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Jawaban</label>
        <textarea
          v-model="drafts[q.id]"
          placeholder="Tulis jawaban untuk pengguna ini…"
          class="min-h-[92px] w-full resize-y rounded-[11px] border border-border-input px-3.5 py-3 text-brand-navy"
        />
        <p v-if="errors[q.id]" class="mt-1.5 text-[12.5px] font-semibold text-danger">{{ errors[q.id] }}</p>
        <p class="mt-1.5 text-[12px] leading-relaxed text-text-faint">
          Belum ada layanan email terhubung — jawaban ini tersimpan sebagai catatan, kirim balasannya lewat kontak di atas.
        </p>
      </div>

      <div class="mt-3.5 flex flex-wrap items-center justify-between gap-3 border-t border-border-divider-2 pt-3.5">
        <div class="flex gap-1.5">
          <button
            v-for="opt in STATUS_OPTIONS"
            :key="opt"
            type="button"
            class="rounded-[9px] px-3.5 py-1.5 text-[12.5px] font-bold"
            :class="opt === q.status ? '' : 'text-text-faint'"
            :style="opt === q.status ? { background: q.statusBg, color: q.statusColor } : { background: '#F4F0E7' }"
            @click="dashboard.setQuestionStatus(q.id, opt)"
          >
            {{ opt }}
          </button>
        </div>
        <div class="flex gap-2">
          <button
            v-if="openId === q.id"
            type="button"
            class="rounded-[11px] border border-border-input px-4 py-2.5 font-bold text-text-muted"
            @click="openId = null"
          >
            Batal
          </button>
          <button
            type="button"
            class="rounded-[11px] bg-brand-navy px-5 py-2.5 font-extrabold text-white disabled:opacity-60"
            :disabled="saving[q.id]"
            @click="openId === q.id ? save(q.id) : (openId = q.id)"
          >
            {{ saving[q.id] ? 'Menyimpan…' : openId === q.id ? 'Simpan jawaban' : q.answer ? 'Ubah jawaban' : 'Tulis jawaban' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
