import type { CategoryName, CategoryStyle, LocationName } from '@/types'
import iconKuliner from '@/assets/icons/categories/kuliner.svg'
import iconPenginapan from '@/assets/icons/categories/penginapan.svg'
import iconFashion from '@/assets/icons/categories/fashion.svg'
import iconOlehOleh from '@/assets/icons/categories/oleh-oleh.svg'
import iconJasa from '@/assets/icons/categories/jasa.svg'
import iconMinuman from '@/assets/icons/categories/minuman.svg'
import iconSayurBuah from '@/assets/icons/categories/sayur-buah.svg'

export const CAT: Record<CategoryName, CategoryStyle> = {
  Kuliner: { accent: '#C98A2E', soft: '#F7EDDC', initial: 'K', icon: iconKuliner },
  Penginapan: { accent: '#3E8E82', soft: '#E3EFED', initial: 'P', icon: iconPenginapan },
  Fashion: { accent: '#2C5EAD', soft: '#E6EDF8', initial: 'F', icon: iconFashion },
  'Oleh-Oleh': { accent: '#1591DC', soft: '#E1F1FB', initial: 'O', icon: iconOlehOleh },
  Jasa: { accent: '#5B6672', soft: '#EEF0F2', initial: 'J', icon: iconJasa },
  Minuman: { accent: '#438A5E', soft: '#E6F2E9', initial: 'M', icon: iconMinuman },
  'Toko Sayur & Buah': { accent: '#6B8E23', soft: '#EEF4DF', initial: 'S', icon: iconSayurBuah },
}

export const CATEGORY_NAMES: CategoryName[] = [
  'Kuliner',
  'Penginapan',
  'Fashion',
  'Oleh-Oleh',
  'Jasa',
  'Minuman',
  'Toko Sayur & Buah',
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
