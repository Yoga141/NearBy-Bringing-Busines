import { ref } from 'vue'
import { defineStore } from 'pinia'
import { ApiError, apiFetch } from '@/lib/api'
import {
  ExcelParseError,
  ITEM_SPEC,
  UMKM_SPEC,
  exportTemplateXlsx,
  exportXlsx,
  parseXlsx,
  type SheetSpec,
} from '@/lib/excel'

export type RowAction = 'create' | 'update' | 'error'

export interface PreviewRow {
  row: number
  action: RowAction
  id?: number | null
  name: string
  messages: string[]
}

export interface PreviewSummary {
  create: number
  update: number
  error: number
}

interface PreviewResponse {
  summary: PreviewSummary
  rows: PreviewRow[]
}

/**
 * Excel export / import for one dataset, shared by the admin and owner panels.
 *
 * Each dataset gets its own store instance so that a product import in
 * progress can't be clobbered by the UMKM card's state sitting on the same
 * page — the two cards are shown together.
 */
function definePortStore(id: string, spec: SheetSpec, basePath: string) {
  return defineStore(id, () => {
    const exporting = ref(false)
    const analysing = ref(false)
    const committing = ref(false)
    const error = ref('')

    /** Non-null while the confirmation step is open. */
    const preview = ref<PreviewResponse | null>(null)
    /** The parsed rows behind the current preview, replayed on confirm. */
    let pendingRows: Record<string, unknown>[] = []
    const done = ref<PreviewSummary | null>(null)

    function message(e: unknown, fallback: string): string {
      if (e instanceof ExcelParseError) return e.message
      if (e instanceof ApiError) return e.firstError
      return fallback
    }

    async function downloadExport() {
      exporting.value = true
      error.value = ''
      try {
        const res = await apiFetch<{ isAdmin: boolean; rows: Record<string, unknown>[] }>(`${basePath}/export`)
        if (!res.rows.length) {
          error.value = 'Belum ada data untuk diekspor.'
          return
        }
        await exportXlsx(spec, res.rows, res.isAdmin)
      } catch (e) {
        error.value = message(e, 'Gagal mengunduh data. Coba lagi.')
      } finally {
        exporting.value = false
      }
    }

    async function downloadTemplate(isAdmin: boolean) {
      error.value = ''
      try {
        await exportTemplateXlsx(spec, isAdmin)
      } catch (e) {
        error.value = message(e, 'Gagal membuat template.')
      }
    }

    /** Parses the chosen file and asks the API what the import would do. */
    async function analyse(file: File, isAdmin: boolean) {
      analysing.value = true
      error.value = ''
      done.value = null
      preview.value = null
      try {
        const rows = await parseXlsx(spec, file, isAdmin)
        pendingRows = rows
        preview.value = await apiFetch<PreviewResponse>(`${basePath}/preview`, {
          method: 'POST',
          body: JSON.stringify({ rows }),
        })
      } catch (e) {
        pendingRows = []
        error.value = message(e, 'Gagal memeriksa file. Coba lagi.')
      } finally {
        analysing.value = false
      }
    }

    /** Applies the previewed rows. The API re-validates before writing anything. */
    async function commit(): Promise<boolean> {
      if (!pendingRows.length) return false
      committing.value = true
      error.value = ''
      try {
        const res = await apiFetch<{ summary: PreviewSummary }>(`${basePath}/commit`, {
          method: 'POST',
          body: JSON.stringify({ rows: pendingRows }),
        })
        done.value = res.summary
        preview.value = null
        pendingRows = []
        return true
      } catch (e) {
        error.value = message(e, 'Gagal menyimpan impor. Tidak ada data yang diubah.')
        return false
      } finally {
        committing.value = false
      }
    }

    function cancel() {
      preview.value = null
      pendingRows = []
      error.value = ''
    }

    function dismissDone() {
      done.value = null
    }

    return {
      exporting,
      analysing,
      committing,
      error,
      preview,
      done,
      downloadExport,
      downloadTemplate,
      analyse,
      commit,
      cancel,
      dismissDone,
    }
  })
}

export const useUmkmPortStore = definePortStore('umkmPort', UMKM_SPEC, '/umkm-excel')
export const useItemPortStore = definePortStore('umkmItemPort', ITEM_SPEC, '/umkm-item-excel')

export type PortStore = ReturnType<typeof useUmkmPortStore>
