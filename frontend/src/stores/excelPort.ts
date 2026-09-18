import { ref } from 'vue'
import { defineStore } from 'pinia'
import { ApiError, apiDownload, apiFetch } from '@/lib/api'

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

/** Largest file the API accepts (ExcelPortController::MAX_UPLOAD_KB). */
const MAX_FILE_BYTES = 5 * 1024 * 1024

/**
 * Excel export / import for one dataset, shared by the admin and owner panels.
 *
 * The .xlsx files are built and read by the backend (see ExcelPortController):
 * downloads come back as ready-made files, and an import uploads the file
 * itself - once to preview, and again on confirm, where the server re-checks
 * everything from scratch before writing. The browser never parses a sheet,
 * so no spreadsheet library ships in the frontend bundle.
 *
 * Each dataset gets its own store instance so that a product import in
 * progress can't be clobbered by the UMKM card's state on the same page.
 */
function definePortStore(id: string, basePath: string, exportPrefix: string, templateName: string) {
  return defineStore(id, () => {
    const exporting = ref(false)
    const templating = ref(false)
    const analysing = ref(false)
    const committing = ref(false)
    const error = ref('')

    /** Non-null while the confirmation step is open. */
    const preview = ref<PreviewResponse | null>(null)
    /** The file behind the current preview, re-sent on confirm. */
    let pendingFile: File | null = null
    const done = ref<PreviewSummary | null>(null)

    function message(e: unknown, fallback: string): string {
      return e instanceof ApiError ? e.firstError : fallback
    }

    function upload(path: string, file: File) {
      const form = new FormData()
      form.append('file', file)
      return apiFetch<PreviewResponse & { message?: string }>(`${basePath}/${path}`, { method: 'POST', body: form })
    }

    async function downloadExport() {
      exporting.value = true
      error.value = ''
      try {
        const stamp = new Date().toISOString().slice(0, 10)
        await apiDownload(`${basePath}/download`, `${exportPrefix}-${stamp}.xlsx`)
      } catch (e) {
        error.value = message(e, 'Gagal mengunduh data. Coba lagi.')
      } finally {
        exporting.value = false
      }
    }

    async function downloadTemplate() {
      templating.value = true
      error.value = ''
      try {
        await apiDownload(`${basePath}/template`, templateName)
      } catch (e) {
        error.value = message(e, 'Gagal membuat template.')
      } finally {
        templating.value = false
      }
    }

    /** Uploads the chosen file and asks the API what the import would do. */
    async function analyse(file: File) {
      error.value = ''
      done.value = null
      preview.value = null
      pendingFile = null

      if (!/\.xlsx$/i.test(file.name)) {
        error.value = 'File tidak bisa dibaca. Pastikan formatnya .xlsx (bukan .xls atau .csv).'
        return
      }
      if (file.size > MAX_FILE_BYTES) {
        error.value = 'Ukuran file maksimal 5 MB.'
        return
      }

      analysing.value = true
      try {
        preview.value = await upload('preview', file)
        pendingFile = file
      } catch (e) {
        error.value = message(e, 'Gagal memeriksa file. Coba lagi.')
      } finally {
        analysing.value = false
      }
    }

    /** Applies the previewed file. The API re-validates before writing anything. */
    async function commit(): Promise<boolean> {
      if (!pendingFile) return false
      committing.value = true
      error.value = ''
      try {
        const res = await upload('commit', pendingFile)
        done.value = res.summary
        preview.value = null
        pendingFile = null
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
      pendingFile = null
      error.value = ''
    }

    function dismissDone() {
      done.value = null
    }

    return {
      exporting,
      templating,
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

export const useUmkmPortStore = definePortStore('umkmPort', '/umkm-excel', 'umkm-nearby', 'template-impor-umkm.xlsx')
export const useItemPortStore = definePortStore(
  'umkmItemPort',
  '/umkm-item-excel',
  'produk-nearby',
  'template-impor-produk.xlsx',
)

export type PortStore = ReturnType<typeof useUmkmPortStore>
