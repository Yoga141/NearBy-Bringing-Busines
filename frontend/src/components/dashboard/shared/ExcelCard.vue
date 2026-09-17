<script setup lang="ts">
import { computed, ref } from 'vue'
import { useItemPortStore, useUmkmPortStore } from '@/stores/excelPort'
import ImportPreviewModal from './ImportPreviewModal.vue'
import ArrowDownIcon from '@/components/shared/ArrowDownIcon.vue'
import ArrowUpIcon from '@/components/shared/ArrowUpIcon.vue'

const props = withDefaults(
  defineProps<{ isAdmin: boolean; dataset?: 'umkm' | 'produk' }>(),
  { dataset: 'umkm' },
)
const emit = defineEmits<{ imported: [] }>()

const isUmkm = computed(() => props.dataset === 'umkm')

// Each dataset has its own store instance, so both cards can sit on the same
// page without sharing a preview or an error banner.
const umkmPort = useUmkmPortStore()
const itemPort = useItemPortStore()
const port = computed(() => (isUmkm.value ? umkmPort : itemPort))

const title = computed(() => (isUmkm.value ? 'Export & Import Excel — UMKM' : 'Export & Import Excel — Produk'))

const intro = computed(() => {
  if (isUmkm.value) {
    return props.isAdmin
      ? 'Unduh seluruh data UMKM sebagai .xlsx, atau unggah file untuk menambah dan memperbarui data secara massal.'
      : 'Unduh data UMKM milikmu sebagai .xlsx, atau unggah file untuk memperbarui dan menambah UMKM sekaligus.'
  }
  return props.isAdmin
    ? 'Unduh seluruh daftar produk sebagai .xlsx, atau unggah file untuk menambah dan memperbarui produk secara massal.'
    : 'Unduh daftar produk dari UMKM milikmu, atau unggah file untuk menambah dan memperbarui produk sekaligus.'
})

/** Only a new UMKM waits for an admin; products go live with their UMKM. */
const needsVerification = computed(() => isUmkm.value && !props.isAdmin)

const nameLabel = computed(() => (isUmkm.value ? 'Nama Usaha' : 'Nama Produk'))

const fileInput = ref<HTMLInputElement | null>(null)

function pickFile() {
  port.value.dismissDone()
  fileInput.value?.click()
}

async function onFile(e: Event) {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  // Reset first so choosing the same file twice in a row still fires change.
  input.value = ''
  if (file) await port.value.analyse(file, props.isAdmin)
}

async function confirmImport() {
  if (await port.value.commit()) emit('imported')
}
</script>

<template>
  <div class="mb-[18px] rounded-[18px] border border-border-card bg-white p-5 shadow-[0_4px_16px_rgba(19,50,77,.04)]">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div class="min-w-0">
        <div class="text-[16px] font-extrabold text-brand-navy">{{ title }}</div>
        <p class="mt-1 max-w-[560px] text-[13.5px] leading-relaxed text-text-muted">{{ intro }}</p>
      </div>

      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-[11px] border border-border-input bg-white px-4 py-2.5 text-[13.5px] font-bold text-brand-navy hover:bg-surface-alt"
          @click="port.downloadTemplate(isAdmin)"
        >
          Unduh template
        </button>
        <button
          type="button"
          class="flex items-center gap-1.5 rounded-[11px] border border-border-input bg-white px-4 py-2.5 text-[13.5px] font-bold text-brand-navy hover:bg-surface-alt disabled:opacity-60"
          :disabled="port.exporting"
          @click="port.downloadExport"
        >
          <template v-if="port.exporting">Menyiapkan…</template>
          <template v-else><ArrowDownIcon size="14px" /> Unduh Excel</template>
        </button>
        <button
          type="button"
          class="flex items-center gap-1.5 rounded-[11px] bg-brand-blue px-5 py-2.5 text-[13.5px] font-extrabold text-white shadow-[0_6px_16px_rgba(44,94,173,.28)] disabled:opacity-60"
          :disabled="port.analysing"
          @click="pickFile"
        >
          <template v-if="port.analysing">Memeriksa…</template>
          <template v-else><ArrowUpIcon size="14px" /> Impor Excel</template>
        </button>
        <input
          ref="fileInput"
          type="file"
          accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
          class="hidden"
          @change="onFile"
        />
      </div>
    </div>

    <p
      v-if="port.error"
      class="mt-3.5 rounded-[11px] border border-danger-border bg-danger-tint px-3.5 py-2.5 text-[13px] font-semibold text-danger"
    >
      {{ port.error }}
    </p>

    <div
      v-if="port.done"
      class="mt-3.5 flex flex-wrap items-center gap-2.5 rounded-[11px] bg-teal-tint px-3.5 py-2.5 text-[13px] font-semibold text-teal-deep"
    >
      <span>
        Impor selesai — {{ port.done.create }} data baru, {{ port.done.update }} diperbarui.
        <template v-if="needsVerification && port.done.create">
          Data baru menunggu verifikasi admin sebelum tampil di website.
        </template>
      </span>
      <button type="button" class="ml-auto font-bold underline" @click="port.dismissDone">Tutup</button>
    </div>
  </div>

  <ImportPreviewModal
    v-if="port.preview"
    :preview="port.preview"
    :name-label="nameLabel"
    :needs-verification="needsVerification"
    :committing="port.committing"
    @cancel="port.cancel"
    @confirm="confirmImport"
  />
</template>
