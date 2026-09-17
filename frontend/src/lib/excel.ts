/**
 * Excel (.xlsx) export & import for the dashboard.
 *
 * The spreadsheet is built and parsed here in the browser rather than on the
 * server: the backend has no spreadsheet library available, and keeping the
 * file handling client-side means the API only ever deals in plain JSON rows.
 *
 * Everything below is driven by a {@link SheetSpec}, so a dataset is added by
 * describing its columns — see `UMKM_SPEC` and `ITEM_SPEC` at the bottom.
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
  /**
   * How the cell and the API value differ:
   * - `number` — read back as a number (ids).
   * - `boolean` — written as Ya/Tidak, read back as a real boolean.
   * - `titleCase` — stored lowercase in the API, shown title-cased in the sheet.
   */
  type?: 'number' | 'boolean' | 'titleCase'
  /** Only present in an admin's sheet. */
  adminOnly?: boolean
  /** Exported for context, but ignored when importing (derived figures). */
  readOnly?: boolean
  /** Example value used in the downloadable template. */
  example?: string
}

/** Everything that distinguishes one dataset's spreadsheet from another's. */
export interface SheetSpec {
  /** Worksheet tab name, e.g. "UMKM". */
  sheetName: string
  columns: ColumnDef[]
  /** Export filename, before the `-YYYY-MM-DD.xlsx` stamp. */
  exportPrefix: string
  templateFilename: string
  /** Column that must appear in an uploaded sheet for it to make sense. */
  requiredColumn: { key: string; header: string }
  /** Lines for the "Petunjuk" sheet of the template. */
  guide: (isAdmin: boolean) => string[]
}

function columnsFor(spec: SheetSpec, isAdmin: boolean): ColumnDef[] {
  return spec.columns.filter((c) => isAdmin || !c.adminOnly)
}

function toSheetValue(col: ColumnDef, value: unknown): string | number | null {
  if (value === null || value === undefined || value === '') return null
  if (col.type === 'boolean') return value ? 'Ya' : 'Tidak'
  if (col.type === 'titleCase') {
    const s = String(value)
    return s.charAt(0).toUpperCase() + s.slice(1)
  }
  return typeof value === 'number' ? value : String(value)
}

const TRUE_WORDS = new Set(['ya', 'true', '1', 'tersedia', 'aktif', 'y'])
const FALSE_WORDS = new Set(['tidak', 'false', '0', 'habis', 'nonaktif', 'n'])

function fromSheetValue(col: ColumnDef, value: unknown): string | number | boolean | null {
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

  if (col.type === 'boolean' && typeof raw === 'boolean') return raw

  const s = String(raw).trim()
  if (s === '') return null

  if (col.type === 'boolean') {
    const word = s.toLowerCase()
    if (TRUE_WORDS.has(word)) return true
    if (FALSE_WORDS.has(word)) return false
    // Anything else is handed to the API unchanged, so it can say what's wrong.
    return s
  }
  if (col.type === 'number') return Number.isFinite(Number(s)) ? Number(s) : s
  return col.type === 'titleCase' ? s.toLowerCase() : s
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

async function buildSheet(spec: SheetSpec, isAdmin: boolean) {
  const ExcelJS = await loadExcelJS()
  const workbook = new ExcelJS.Workbook()
  workbook.creator = 'NearBy Balikpapan'
  workbook.created = new Date()
  const sheet = workbook.addWorksheet(spec.sheetName)
  const cols = columnsFor(spec, isAdmin)
  sheet.columns = cols.map((c) => ({ header: c.header, key: c.key, width: c.width }))
  return { workbook, sheet, cols }
}

/** Writes the given rows to an .xlsx file and starts the download. */
export async function exportXlsx(spec: SheetSpec, rows: Record<string, unknown>[], isAdmin: boolean) {
  const { workbook, sheet, cols } = await buildSheet(spec, isAdmin)

  for (const row of rows) {
    sheet.addRow(Object.fromEntries(cols.map((c) => [c.key, toSheetValue(c, row[c.key])])))
  }
  styleHeader(sheet)

  const stamp = new Date().toISOString().slice(0, 10)
  await download(workbook, `${spec.exportPrefix}-${stamp}.xlsx`)
}

/** Writes an empty sheet with one example row, for filling in from scratch. */
export async function exportTemplateXlsx(spec: SheetSpec, isAdmin: boolean) {
  const { workbook, sheet, cols } = await buildSheet(spec, isAdmin)

  sheet.addRow(Object.fromEntries(cols.map((c) => [c.key, c.example ?? ''])))
  styleHeader(sheet)

  // The rules live on their own sheet, never under the data: a note placed in
  // the data sheet lands in the ID column and re-imports as a bogus row.
  const guide = workbook.addWorksheet('Petunjuk')
  guide.columns = [{ width: 96 }]
  for (const line of spec.guide(isAdmin)) {
    guide.addRow([line])
  }
  guide.getRow(1).font = { bold: true, size: 13 }

  await download(workbook, spec.templateFilename)
}

export class ExcelParseError extends Error {}

/**
 * Reads an .xlsx file into API-shaped rows.
 *
 * Headers are matched case-insensitively and ignoring surrounding spaces, so a
 * sheet someone has lightly reformatted still imports. Read-only columns are
 * dropped here rather than being sent and rejected.
 */
export async function parseXlsx(
  spec: SheetSpec,
  file: File,
  isAdmin: boolean,
): Promise<Record<string, unknown>[]> {
  const ExcelJS = await loadExcelJS()
  const workbook = new ExcelJS.Workbook()
  try {
    await workbook.xlsx.load(await file.arrayBuffer())
  } catch {
    throw new ExcelParseError('File tidak bisa dibaca. Pastikan formatnya .xlsx (bukan .xls atau .csv).')
  }

  const sheet = workbook.worksheets[0]
  if (!sheet) throw new ExcelParseError('File Excel ini tidak punya lembar kerja.')

  const allowed = columnsFor(spec, isAdmin).filter((c) => !c.readOnly)
  const byHeader = new Map(allowed.map((c) => [c.header.toLowerCase(), c]))

  // Map each sheet column index to a field, from the header row.
  const headerRow = sheet.getRow(1)
  const indexToCol = new Map<number, ColumnDef>()
  headerRow.eachCell((cell, col) => {
    const label = String(cell.value ?? '').trim().toLowerCase()
    const def = byHeader.get(label)
    if (def) indexToCol.set(col, def)
  })

  if (!indexToCol.size) {
    throw new ExcelParseError(
      'Baris pertama tidak dikenali sebagai judul kolom. Gunakan tombol "Unduh template" sebagai acuan.',
    )
  }
  if (![...indexToCol.values()].some((c) => c.key === spec.requiredColumn.key)) {
    throw new ExcelParseError(`Kolom "${spec.requiredColumn.header}" tidak ditemukan di file ini.`)
  }

  const rows: Record<string, unknown>[] = []
  sheet.eachRow((row, rowNumber) => {
    if (rowNumber === 1) return

    const parsed: Record<string, unknown> = {}
    let hasValue = false
    for (const [index, col] of indexToCol) {
      const value = fromSheetValue(col, row.getCell(index).value)
      if (value !== null) hasValue = true
      parsed[col.key] = value
    }
    // Skip blank spacer rows and the template's trailing note.
    if (hasValue) rows.push(parsed)
  })

  if (!rows.length) throw new ExcelParseError('Tidak ada baris data di file ini.')

  return rows
}

const CATEGORIES = ['Kuliner', 'Penginapan', 'Fashion', 'Oleh-Oleh', 'Jasa']
const LOCATIONS = [
  'Balikpapan Kota',
  'Balikpapan Utara',
  'Balikpapan Selatan',
  'Balikpapan Timur',
  'Balikpapan Barat',
  'Balikpapan Tengah',
]

/** Shared opening of every template's "Petunjuk" sheet. */
function howToLines(subject: string, sheetName: string): string[] {
  return [
    `Cara memakai template ini`,
    '',
    `1. Isi mulai baris ke-2 pada lembar "${sheetName}". Baris contoh boleh ditimpa atau dihapus.`,
    `2. Kosongkan kolom ID untuk ${subject} baru.`,
    '3. Isi kolom ID untuk memperbarui data yang sudah ada (ambil ID dari hasil "Unduh Excel").',
    '4. Sel yang dibiarkan kosong saat memperbarui berarti "biarkan seperti semula".',
    '',
  ]
}

export const UMKM_SPEC: SheetSpec = {
  sheetName: 'UMKM',
  exportPrefix: 'umkm-nearby',
  templateFilename: 'template-impor-umkm.xlsx',
  requiredColumn: { key: 'name', header: 'Nama Usaha' },
  columns: [
    { header: 'ID', key: 'id', width: 8, type: 'number', example: '' },
    { header: 'Nama Usaha', key: 'name', width: 30, example: 'Warung Contoh Rasa' },
    { header: 'Kategori', key: 'category', width: 16, example: 'Kuliner' },
    { header: 'Wilayah', key: 'location', width: 20, example: 'Balikpapan Kota' },
    { header: 'Alamat', key: 'address', width: 34, example: 'Jl. Contoh No. 1' },
    { header: 'Jam Buka', key: 'hours', width: 20, example: '08.00 - 21.00 WITA' },
    { header: 'Telepon', key: 'phone', width: 18, example: '0812-0000-0000' },
    { header: 'Instagram', key: 'ig', width: 20, example: '@contoh.rasa' },
    { header: 'Kisaran Harga', key: 'price_label', width: 16, example: 'Rp15-50rb' },
    { header: 'Deskripsi Singkat', key: 'tag', width: 40, example: 'Masakan rumahan khas Balikpapan.' },
    { header: 'Status', key: 'status', width: 12, type: 'titleCase', example: 'Aktif' },
    { header: 'Verifikasi', key: 'verification', width: 14, type: 'titleCase', adminOnly: true, example: 'Disetujui' },
    { header: 'Email Pemilik', key: 'owner_email', width: 26, adminOnly: true, readOnly: true },
    { header: 'Rating', key: 'rating', width: 10, readOnly: true },
    { header: 'Jumlah Ulasan', key: 'reviews_count', width: 14, readOnly: true },
    { header: 'Dilihat', key: 'views', width: 10, readOnly: true },
  ],
  guide: (isAdmin) => [
    ...howToLines('data', 'UMKM'),
    `Kategori: ${CATEGORIES.join(', ')}`,
    `Wilayah: ${LOCATIONS.join(', ')}`,
    'Status: Aktif, Libur, Tutup',
    isAdmin ? 'Verifikasi: Menunggu, Disetujui, Ditolak' : '',
    '',
    'Kolom Rating, Jumlah Ulasan, dan Dilihat hanya informasi — perubahannya diabaikan saat impor.',
  ],
}

export const ITEM_SPEC: SheetSpec = {
  sheetName: 'Produk',
  exportPrefix: 'produk-nearby',
  templateFilename: 'template-impor-produk.xlsx',
  requiredColumn: { key: 'name', header: 'Nama Produk' },
  columns: [
    { header: 'ID', key: 'id', width: 8, type: 'number', example: '' },
    { header: 'ID UMKM', key: 'umkm_id', width: 10, type: 'number', example: '1' },
    { header: 'Nama Usaha', key: 'umkm_name', width: 30, readOnly: true },
    { header: 'Nama Produk', key: 'name', width: 30, example: 'Nasi Kuning Spesial' },
    { header: 'Harga', key: 'price', width: 16, example: 'Rp20.000' },
    { header: 'Link Gambar', key: 'img', width: 34, example: '' },
    { header: 'Tersedia', key: 'available', width: 12, type: 'boolean', example: 'Ya' },
  ],
  guide: () => [
    ...howToLines('produk', 'Produk'),
    'Kolom "ID UMKM" wajib diisi untuk produk baru — ambil angkanya dari kolom ID',
    'pada hasil "Unduh Excel" di kartu Export & Import UMKM.',
    '',
    'Tersedia: Ya atau Tidak (kosong dianggap Ya untuk produk baru).',
    'Harga boleh ditulis bebas, misalnya "Rp20.000" atau "20rb".',
    '',
    'Kolom Nama Usaha hanya informasi — perubahannya diabaikan saat impor.',
    'Untuk memindahkan produk ke UMKM lain, ubah kolom ID UMKM-nya.',
  ],
}
