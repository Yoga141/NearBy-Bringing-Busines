/**
 * Excel (.xlsx) export & import for the UMKM dashboard.
 *
 * The spreadsheet is built and parsed here in the browser rather than on the
 * server: the backend has no spreadsheet library available, and keeping the
 * file handling client-side means the API only ever deals in plain JSON rows.
 */
import type { Workbook, Worksheet } from 'exceljs'

/**
 * exceljs is ~950 kB — far too much to sit in the dashboard bundle for the
 * many visits that never touch a spreadsheet. Loading it on first use keeps it
 * in its own chunk, fetched only when someone actually exports or imports.
 */
async function loadExcelJS() {
  return (await import('exceljs')).default
}

/** A column in the sheet, mapping an Indonesian header to an API field. */
export interface ColumnDef {
  header: string
  key: string
  width: number
  /** Only present in an admin's sheet. */
  adminOnly?: boolean
  /** Exported for context, but ignored when importing (derived figures). */
  readOnly?: boolean
  /** Example value used in the downloadable template. */
  example?: string
}

export const UMKM_COLUMNS: ColumnDef[] = [
  { header: 'ID', key: 'id', width: 8, example: '' },
  { header: 'Nama Usaha', key: 'name', width: 30, example: 'Warung Contoh Rasa' },
  { header: 'Kategori', key: 'category', width: 16, example: 'Kuliner' },
  { header: 'Wilayah', key: 'location', width: 20, example: 'Balikpapan Kota' },
  { header: 'Alamat', key: 'address', width: 34, example: 'Jl. Contoh No. 1' },
  { header: 'Jam Buka', key: 'hours', width: 20, example: '08.00 - 21.00 WITA' },
  { header: 'Telepon', key: 'phone', width: 18, example: '0812-0000-0000' },
  { header: 'Instagram', key: 'ig', width: 20, example: '@contoh.rasa' },
  { header: 'Kisaran Harga', key: 'price_label', width: 16, example: 'Rp15-50rb' },
  { header: 'Deskripsi Singkat', key: 'tag', width: 40, example: 'Masakan rumahan khas Balikpapan.' },
  { header: 'Status', key: 'status', width: 12, example: 'Aktif' },
  { header: 'Verifikasi', key: 'verification', width: 14, adminOnly: true, example: 'Disetujui' },
  { header: 'Email Pemilik', key: 'owner_email', width: 26, adminOnly: true, readOnly: true },
  { header: 'Rating', key: 'rating', width: 10, readOnly: true },
  { header: 'Jumlah Ulasan', key: 'reviews_count', width: 14, readOnly: true },
  { header: 'Dilihat', key: 'views', width: 10, readOnly: true },
]

/** Fields stored lowercase in the API but shown title-cased in the sheet. */
const TITLE_CASED = new Set(['status', 'verification'])

function toSheetValue(key: string, value: unknown): string | number | null {
  if (value === null || value === undefined || value === '') return null
  if (TITLE_CASED.has(key)) {
    const s = String(value)
    return s.charAt(0).toUpperCase() + s.slice(1)
  }
  return typeof value === 'number' ? value : String(value)
}

function fromSheetValue(key: string, value: unknown): string | number | null {
  if (value === null || value === undefined) return null
  // A cell can come back as a formula/rich-text object rather than a scalar.
  let raw: unknown = value
  if (typeof raw === 'object') {
    const o = raw as { text?: unknown; result?: unknown; richText?: { text: string }[] }
    if (Array.isArray(o.richText)) raw = o.richText.map((r) => r.text).join('')
    else if (o.text !== undefined) raw = o.text
    else if (o.result !== undefined) raw = o.result
    else raw = String(raw)
  }

  const s = String(raw).trim()
  if (s === '') return null
  if (key === 'id') return Number.isFinite(Number(s)) ? Number(s) : s
  return TITLE_CASED.has(key) ? s.toLowerCase() : s
}

function columnsFor(isAdmin: boolean): ColumnDef[] {
  return UMKM_COLUMNS.filter((c) => isAdmin || !c.adminOnly)
}

function styleHeader(sheet: Worksheet) {
  const header = sheet.getRow(1)
  header.font = { bold: true, color: { argb: 'FFFFFFFF' } }
  header.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF16324B' } }
  header.alignment = { vertical: 'middle' }
  header.height = 22
  sheet.views = [{ state: 'frozen', ySplit: 1 }]
}

async function download(workbook: Workbook, filename: string) {
  const buffer = await workbook.xlsx.writeBuffer()
  const blob = new Blob([buffer], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filename
  document.body.appendChild(a)
  a.click()
  a.remove()
  // Revoke on the next tick so the download has certainly started.
  setTimeout(() => URL.revokeObjectURL(url), 0)
}

async function buildSheet(isAdmin: boolean) {
  const ExcelJS = await loadExcelJS()
  const workbook = new ExcelJS.Workbook()
  workbook.creator = 'NearBy Balikpapan'
  workbook.created = new Date()
  const sheet = workbook.addWorksheet('UMKM')
  const cols = columnsFor(isAdmin)
  sheet.columns = cols.map((c) => ({ header: c.header, key: c.key, width: c.width }))
  return { workbook, sheet, cols }
}

/** Writes the given rows to an .xlsx file and starts the download. */
export async function exportUmkmXlsx(rows: Record<string, unknown>[], isAdmin: boolean) {
  const { workbook, sheet, cols } = await buildSheet(isAdmin)

  for (const row of rows) {
    sheet.addRow(Object.fromEntries(cols.map((c) => [c.key, toSheetValue(c.key, row[c.key])])))
  }
  styleHeader(sheet)

  const stamp = new Date().toISOString().slice(0, 10)
  await download(workbook, `umkm-nearby-${stamp}.xlsx`)
}

/** Writes an empty sheet with one example row, for filling in from scratch. */
export async function exportUmkmTemplateXlsx(isAdmin: boolean) {
  const { workbook, sheet, cols } = await buildSheet(isAdmin)

  sheet.addRow(Object.fromEntries(cols.map((c) => [c.key, c.example ?? ''])))
  styleHeader(sheet)

  // The rules live on their own sheet, never under the data: a note placed in
  // the data sheet lands in the ID column and re-imports as a bogus row.
  const guide = workbook.addWorksheet('Petunjuk')
  guide.columns = [{ width: 96 }]
  for (const line of [
    'Cara memakai template ini',
    '',
    '1. Isi mulai baris ke-2 pada lembar "UMKM". Baris contoh boleh ditimpa atau dihapus.',
    '2. Kosongkan kolom ID untuk data baru.',
    '3. Isi kolom ID untuk memperbarui data yang sudah ada (ambil ID dari hasil "Unduh Excel").',
    '4. Sel yang dibiarkan kosong saat memperbarui berarti "biarkan seperti semula".',
    '',
    `Kategori: ${['Kuliner', 'Penginapan', 'Fashion', 'Oleh-Oleh', 'Jasa'].join(', ')}`,
    `Wilayah: ${['Balikpapan Kota', 'Balikpapan Utara', 'Balikpapan Selatan', 'Balikpapan Timur', 'Balikpapan Barat', 'Balikpapan Tengah'].join(', ')}`,
    'Status: Aktif, Libur, Tutup',
    isAdmin ? 'Verifikasi: Menunggu, Disetujui, Ditolak' : '',
    '',
    'Kolom Rating, Jumlah Ulasan, dan Dilihat hanya informasi — perubahannya diabaikan saat impor.',
  ]) {
    guide.addRow([line])
  }
  guide.getRow(1).font = { bold: true, size: 13 }

  await download(workbook, 'template-impor-umkm.xlsx')
}

export class ExcelParseError extends Error {}

/**
 * Reads an .xlsx file into API-shaped rows.
 *
 * Headers are matched case-insensitively and ignoring surrounding spaces, so a
 * sheet someone has lightly reformatted still imports. Read-only columns are
 * dropped here rather than being sent and rejected.
 */
export async function parseUmkmXlsx(file: File, isAdmin: boolean): Promise<Record<string, unknown>[]> {
  const ExcelJS = await loadExcelJS()
  const workbook = new ExcelJS.Workbook()
  try {
    await workbook.xlsx.load(await file.arrayBuffer())
  } catch {
    throw new ExcelParseError('File tidak bisa dibaca. Pastikan formatnya .xlsx (bukan .xls atau .csv).')
  }

  const sheet = workbook.worksheets[0]
  if (!sheet) throw new ExcelParseError('File Excel ini tidak punya lembar kerja.')

  const allowed = columnsFor(isAdmin).filter((c) => !c.readOnly)
  const byHeader = new Map(allowed.map((c) => [c.header.toLowerCase(), c]))

  // Map each sheet column index to a field, from the header row.
  const headerRow = sheet.getRow(1)
  const indexToKey = new Map<number, string>()
  headerRow.eachCell((cell, col) => {
    const label = String(cell.value ?? '').trim().toLowerCase()
    const def = byHeader.get(label)
    if (def) indexToKey.set(col, def.key)
  })

  if (!indexToKey.size) {
    throw new ExcelParseError(
      'Baris pertama tidak dikenali sebagai judul kolom. Gunakan tombol "Unduh template" sebagai acuan.',
    )
  }
  if (![...indexToKey.values()].includes('name')) {
    throw new ExcelParseError('Kolom "Nama Usaha" tidak ditemukan di file ini.')
  }

  const rows: Record<string, unknown>[] = []
  sheet.eachRow((row, rowNumber) => {
    if (rowNumber === 1) return

    const parsed: Record<string, unknown> = {}
    let hasValue = false
    for (const [col, key] of indexToKey) {
      const value = fromSheetValue(key, row.getCell(col).value)
      if (value !== null) hasValue = true
      parsed[key] = value
    }
    // Skip blank spacer rows and the template's trailing note.
    if (hasValue) rows.push(parsed)
  })

  if (!rows.length) throw new ExcelParseError('Tidak ada baris data di file ini.')

  return rows
}
