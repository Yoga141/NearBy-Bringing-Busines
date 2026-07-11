import type { CategoryName, CategoryStyle, LocationName } from '@/types'

export const CAT: Record<CategoryName, CategoryStyle> = {
  Kuliner: { accent: '#C98A2E', soft: '#F7EDDC', initial: 'K' },
  Penginapan: { accent: '#3E8E82', soft: '#E3EFED', initial: 'P' },
  Fashion: { accent: '#2C5EAD', soft: '#E6EDF8', initial: 'F' },
  'Oleh-Oleh': { accent: '#1591DC', soft: '#E1F1FB', initial: 'O' },
  Jasa: { accent: '#5B6672', soft: '#EEF0F2', initial: 'J' },
}

export const CATEGORY_NAMES: CategoryName[] = [
  'Kuliner',
  'Penginapan',
  'Fashion',
  'Oleh-Oleh',
  'Jasa',
]

/** Filter chip order used on the Daftar UMKM page (includes "Semua"). */
export const CATEGORY_FILTERS: string[] = ['Semua', ...CATEGORY_NAMES]

/** Canonical kecamatan order used in filters, register/edit-UMKM selects. */
export const LOCATION_NAMES: LocationName[] = [
  'Balikpapan Kota',
  'Balikpapan Utara',
  'Balikpapan Selatan',
  'Balikpapan Timur',
  'Balikpapan Barat',
  'Balikpapan Tengah',
]

export const LOCATION_FILTERS: string[] = ['Semua', ...LOCATION_NAMES]

/** Homepage "Jelajahi per wilayah" cards: [full name, short label]. */
export const REGION_CARDS: [LocationName, string][] = [
  ['Balikpapan Kota', 'Kota'],
  ['Balikpapan Utara', 'Utara'],
  ['Balikpapan Selatan', 'Selatan'],
  ['Balikpapan Timur', 'Timur'],
  ['Balikpapan Barat', 'Barat'],
  ['Balikpapan Tengah', 'Tengah'],
]
