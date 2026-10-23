import type { Component } from 'vue'
import PlateIcon from '@/components/shared/PlateIcon.vue'
import BowlIcon from '@/components/shared/BowlIcon.vue'
import DrinkIcon from '@/components/shared/DrinkIcon.vue'
import CoffeeIcon from '@/components/shared/CoffeeIcon.vue'
import SnackIcon from '@/components/shared/SnackIcon.vue'
import ShirtIcon from '@/components/shared/ShirtIcon.vue'
import PantsIcon from '@/components/shared/PantsIcon.vue'
import BedIcon from '@/components/shared/BedIcon.vue'
import ServiceIcon from '@/components/shared/ServiceIcon.vue'
import ScissorsIcon from '@/components/shared/ScissorsIcon.vue'
import CraftIcon from '@/components/shared/CraftIcon.vue'
import PackageIcon from '@/components/shared/PackageIcon.vue'

export type MenuIconCategory = 'Semua' | 'Kuliner' | 'Fashion' | 'Penginapan' | 'Jasa' | 'Kerajinan'

export interface MenuIconOption {
  key: string
  label: string
  shortLabel: string
  category: 'Kuliner' | 'Fashion' | 'Penginapan' | 'Jasa' | 'Kerajinan'
  icon: Component
}

export const MENU_ICON_CATEGORIES: MenuIconCategory[] = [
  'Semua',
  'Kuliner',
  'Fashion',
  'Penginapan',
  'Jasa',
  'Kerajinan',
]

export const MENU_ICON_OPTIONS: MenuIconOption[] = [
  // Kuliner
  { key: 'icon:plate', label: 'Makanan / Piring', shortLabel: 'Makanan', category: 'Kuliner', icon: PlateIcon },
  { key: 'icon:bowl', label: 'Mangkok / Mie / Sup', shortLabel: 'Mangkok', category: 'Kuliner', icon: BowlIcon },
  { key: 'icon:drink', label: 'Minuman Dingin', shortLabel: 'Minuman', category: 'Kuliner', icon: DrinkIcon },
  { key: 'icon:coffee', label: 'Kopi / Teh Hangat', shortLabel: 'Kopi', category: 'Kuliner', icon: CoffeeIcon },
  { key: 'icon:snack', label: 'Cemilan / Roti / Kue', shortLabel: 'Cemilan', category: 'Kuliner', icon: SnackIcon },

  // Fashion
  { key: 'icon:shirt', label: 'Baju / Kaos / Kemeja / Batik', shortLabel: 'Baju', category: 'Fashion', icon: ShirtIcon },
  { key: 'icon:pants', label: 'Celana / Bawahan / Rok', shortLabel: 'Celana', category: 'Fashion', icon: PantsIcon },

  // Penginapan
  { key: 'icon:bed', label: 'Kamar / Tipe Bed / Penginapan', shortLabel: 'Kamar', category: 'Penginapan', icon: BedIcon },

  // Jasa
  { key: 'icon:service', label: 'Layanan Servis / Perbaikan / Bengkel', shortLabel: 'Servis', category: 'Jasa', icon: ServiceIcon },
  { key: 'icon:scissors', label: 'Barbershop / Potong / Jahit', shortLabel: 'Potong/Jahit', category: 'Jasa', icon: ScissorsIcon },

  // Kerajinan & Oleh-oleh
  { key: 'icon:craft', label: 'Kerajinan / Anyaman / Rotan / Kriya', shortLabel: 'Kerajinan', category: 'Kerajinan', icon: CraftIcon },
  { key: 'icon:package', label: 'Oleh-oleh / Paket / Suvenir', shortLabel: 'Oleh-oleh', category: 'Kerajinan', icon: PackageIcon },
]

/** Map of key -> option for O(1) lookup. */
export const ICON_BY_KEY = new Map<string, MenuIconOption>(
  MENU_ICON_OPTIONS.map((opt) => [opt.key, opt]),
)

/** Static pre-filtered icon arrays by category to avoid allocations on render. */
export const ICONS_BY_CATEGORY: Record<MenuIconCategory, MenuIconOption[]> = {
  Semua: MENU_ICON_OPTIONS,
  Kuliner: MENU_ICON_OPTIONS.filter((o) => o.category === 'Kuliner'),
  Fashion: MENU_ICON_OPTIONS.filter((o) => o.category === 'Fashion'),
  Penginapan: MENU_ICON_OPTIONS.filter((o) => o.category === 'Penginapan'),
  Jasa: MENU_ICON_OPTIONS.filter((o) => o.category === 'Jasa'),
  Kerajinan: MENU_ICON_OPTIONS.filter((o) => o.category === 'Kerajinan'),
}

/**
 * Checks whether an img string represents an actual uploaded or external photo URL.
 */
export function isPhotoUrl(val?: string | null): boolean {
  if (!val) return false
  const trimmed = val.trim()
  if (trimmed.startsWith('icon:')) return false
  return (
    trimmed.startsWith('/api/') ||
    trimmed.startsWith('http://') ||
    trimmed.startsWith('https://') ||
    trimmed.startsWith('data:image/') ||
    trimmed.startsWith('/')
  )
}

const resolveCache = new Map<string, MenuIconOption>()
const MAX_CACHE_SIZE = 300

/**
 * Resolves which icon component to use when a menu item does not have a real photo.
 * Falls back intelligently to inferred icon based on the item name and business category.
 * Cached for O(1) instantaneous access.
 */
export function resolveMenuIcon(img?: string | null, name?: string, businessCategory?: string): MenuIconOption {
  if (img && img.startsWith('icon:')) {
    const found = ICON_BY_KEY.get(img)
    if (found) return found
  }

  const cacheKey = `${img || ''}|${name || ''}|${businessCategory || ''}`
  const cached = resolveCache.get(cacheKey)
  if (cached) return cached

  const n = (name || '').toLowerCase()
  const bCat = (businessCategory || '').toLowerCase()

  let result: MenuIconOption = MENU_ICON_OPTIONS[0]

  // 1. Check specific item name keywords

  // Lodging keywords
  if (n.includes('kamar') || n.includes('bed') || n.includes('malam') || n.includes('deluxe') || n.includes('suite') || n.includes('superior') || n.includes('standard') || n.includes('villa') || n.includes('homestay') || n.includes('kost')) {
    result = ICON_BY_KEY.get('icon:bed')!
  } else if (n.includes('celana') || n.includes('jeans') || n.includes('rok') || n.includes('kulot') || n.includes('bawahan') || n.includes('short') || n.includes('trousers')) {
    result = ICON_BY_KEY.get('icon:pants')!
  } else if (n.includes('baju') || n.includes('kaos') || n.includes('t-shirt') || n.includes('shirt') || n.includes('batik') || n.includes('kemeja') || n.includes('jaket') || n.includes('hoodie') || n.includes('gamis') || n.includes('tunik') || n.includes('atasan') || n.includes('kebaya')) {
    result = ICON_BY_KEY.get('icon:shirt')!
  } else if (n.includes('potong') || n.includes('cukur') || n.includes('barber') || n.includes('pangkas') || n.includes('jahit') || n.includes('makloon') || n.includes('vermak')) {
    result = ICON_BY_KEY.get('icon:scissors')!
  } else if (n.includes('servis') || n.includes('service') || n.includes('ganti') || n.includes('oli') || n.includes('reparasi') || n.includes('cuci') || n.includes('laundry') || n.includes('tune up') || n.includes('bengkel') || n.includes('perbaikan') || n.includes('instalasi')) {
    result = ICON_BY_KEY.get('icon:service')!
  } else if (n.includes('rotan') || n.includes('anyaman') || n.includes('tas') || n.includes('dompet') || n.includes('kriya') || n.includes('ukir') || n.includes('kerajinan') || n.includes('rajut')) {
    result = ICON_BY_KEY.get('icon:craft')!
  } else if (n.includes('oleh') || n.includes('paket') || n.includes('hampers') || n.includes('suvenir') || n.includes('souvenir') || n.includes('box')) {
    result = ICON_BY_KEY.get('icon:package')!
  } else if (n.includes('kopi') || n.includes('coffee') || n.includes('espresso') || n.includes('latte') || n.includes('cappuccino') || n.includes('americano')) {
    result = ICON_BY_KEY.get('icon:coffee')!
  } else if (
    n.includes('mie') ||
    n.includes('bakso') ||
    n.includes('soto') ||
    n.includes('sup') ||
    n.includes('soup') ||
    n.includes('ramen') ||
    n.includes('rawon') ||
    n.includes('bubur') ||
    n.includes('bihun') ||
    n.includes('kwetiau')
  ) {
    result = ICON_BY_KEY.get('icon:bowl')!
  } else if (
    n.includes('es ') ||
    n.includes('jus') ||
    n.includes('juice') ||
    n.includes('teh') ||
    n.includes('tea') ||
    n.includes('drink') ||
    n.includes('soda') ||
    n.includes('boba') ||
    n.includes('milk') ||
    n.includes('susu') ||
    n.includes('shake')
  ) {
    result = ICON_BY_KEY.get('icon:drink')!
  } else if (
    n.includes('snack') ||
    n.includes('roti') ||
    n.includes('kue') ||
    n.includes('cake') ||
    n.includes('pisang') ||
    n.includes('keripik') ||
    n.includes('pastry') ||
    n.includes('donat') ||
    n.includes('amplang') ||
    n.includes('cookies')
  ) {
    result = ICON_BY_KEY.get('icon:snack')!
  } else if (bCat.includes('fashion') || bCat.includes('pakaian')) {
    result = ICON_BY_KEY.get('icon:shirt')!
  } else if (bCat.includes('penginapan') || bCat.includes('hotel') || bCat.includes('wisma')) {
    result = ICON_BY_KEY.get('icon:bed')!
  } else if (bCat.includes('jasa') || bCat.includes('servis')) {
    result = ICON_BY_KEY.get('icon:service')!
  } else if (bCat.includes('oleh') || bCat.includes('kriya') || bCat.includes('kerajinan')) {
    result = ICON_BY_KEY.get('icon:craft')!
  }

  if (resolveCache.size >= MAX_CACHE_SIZE) {
    resolveCache.clear()
  }
  resolveCache.set(cacheKey, result)
  return result
}
