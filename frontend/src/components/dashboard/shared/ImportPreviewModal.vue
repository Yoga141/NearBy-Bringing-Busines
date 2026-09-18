<script setup lang="ts">
import { computed } from 'vue'
import BaseModal from '@/components/shared/BaseModal.vue'
import type { PreviewRow, PreviewSummary } from '@/stores/excelPort'

const props = defineProps<{
  preview: { summary: PreviewSummary; rows: PreviewRow[] }
  /** Header for the name column - "Nama Usaha" or "Nama Produk". */
  nameLabel: string
  /** Whether new rows will queue for admin verification once imported. */
  needsVerification: boolean
  committing: boolean
}>()
const emit = defineEmits<{ cancel: []; confirm: [] }>()

const blocked = computed(() => props.preview.summary.error > 0)
const total = computed(() => props.preview.rows.length)

const ACTION_META: Record<string, { label: string; c: string; b: string }> = {
  create: { label: 'Baru', c: '#2E7D6E', b: '#E3EFED' },
  update: { label: 'Diperbarui', c: '#2C5EAD', b: '#E6EDF8' },
  error: { label: 'Bermasalah', c: '#C0472F', b: '#F8E6E0' },
}

// Problem rows first: they're the ones that need acting on.
const ordered = computed(() =>
  [...props.preview.rows].sort((a, b) => (a.action === 'error' ? -1 : b.action === 'error' ? 1 : a.row - b.row)),
)
</script>

<template>
  <BaseModal max-width="max-w-[720px]" @close="emit('cancel')">
    <div class="border-b border-border-divider-2 px-6 py-5">
      <div class="text-[19px] font-extrabold text-brand-navy">Pratinjau impor</div>
      <p class="mt-1 text-[13.5px] leading-relaxed text-text-muted">
        Belum ada data yang tersimpan. Periksa ringkasan di bawah, lalu konfirmasi.
      </p>
    </div>

    <div class="grid grid-cols-3 gap-3 px-6 pt-5">
      <div class="rounded-[13px] bg-teal-tint px-4 py-3">
        <div class="text-[22px] font-extrabold text-teal-deep">{{ preview.summary.create }}</div>
        <div class="text-[12.5px] font-bold text-teal-deep">Data baru</div>
      </div>
      <div class="rounded-[13px] bg-brand-blue-tint px-4 py-3">
        <div class="text-[22px] font-extrabold text-brand-blue">{{ preview.summary.update }}</div>
        <div class="text-[12.5px] font-bold text-brand-blue">Diperbarui</div>
      </div>
      <div class="rounded-[13px] px-4 py-3" :class="blocked ? 'bg-danger-tint' : 'bg-surface-alt'">
        <div class="text-[22px] font-extrabold" :class="blocked ? 'text-danger' : 'text-text-faint'">
          {{ preview.summary.error }}
        </div>
        <div class="text-[12.5px] font-bold" :class="blocked ? 'text-danger' : 'text-text-faint'">Bermasalah</div>
      </div>
    </div>

    <p
      v-if="blocked"
      class="mx-6 mt-4 rounded-[11px] border border-danger-border bg-danger-tint-2 px-3.5 py-2.5 text-[13px] font-semibold text-danger"
    >
      Impor tidak bisa dijalankan selama masih ada baris bermasalah. Perbaiki baris di bawah pada file Excel-mu, lalu unggah ulang.
    </p>

    <div class="mt-4 max-h-[320px] overflow-y-auto px-6">
      <table class="w-full border-collapse text-left">
        <thead class="sticky top-0 bg-white">
          <tr class="text-[11.5px] font-extrabold tracking-[.06em] text-text-faint uppercase">
            <th class="border-b border-border-divider-2 py-2 pr-3 w-[70px]">Baris</th>
            <th class="border-b border-border-divider-2 py-2 pr-3">{{ nameLabel }}</th>
            <th class="border-b border-border-divider-2 py-2 w-[130px]">Hasil</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="r in ordered" :key="r.row" class="align-top">
            <td class="border-b border-border-divider py-2.5 pr-3 text-[13px] font-bold text-text-faint">
              {{ r.row }}
            </td>
            <td class="border-b border-border-divider py-2.5 pr-3">
              <div class="text-[13.5px] font-bold text-brand-navy">{{ r.name || '-' }}</div>
              <ul v-if="r.messages.length" class="mt-1 list-disc pl-4">
                <li v-for="(m, i) in r.messages" :key="i" class="text-[12.5px] leading-relaxed text-danger">{{ m }}</li>
              </ul>
            </td>
            <td class="border-b border-border-divider py-2.5">
              <span
                class="rounded-full px-[11px] py-1 text-[11.5px] font-bold"
                :style="{ background: ACTION_META[r.action].b, color: ACTION_META[r.action].c }"
              >
                {{ ACTION_META[r.action].label }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-1 flex flex-wrap items-center justify-between gap-3 border-t border-border-divider-2 px-6 py-4">
      <div class="text-[12.5px] font-semibold text-text-faint">
        {{ total }} baris terbaca
        <template v-if="needsVerification && preview.summary.create">
          · data baru akan menunggu verifikasi admin
        </template>
      </div>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-[11px] border border-border-input px-4 py-2.5 font-bold text-text-muted"
          @click="emit('cancel')"
        >
          Batal
        </button>
        <button
          type="button"
          class="rounded-[11px] bg-brand-navy px-5 py-2.5 font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="blocked || committing || !(preview.summary.create + preview.summary.update)"
          @click="emit('confirm')"
        >
          {{ committing ? 'Menyimpan…' : 'Konfirmasi impor' }}
        </button>
      </div>
    </div>
  </BaseModal>
</template>
