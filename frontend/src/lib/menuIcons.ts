import type { Component } from 'vue'
import PlateIcon from '@/components/shared/PlateIcon.vue'
import DrinkIcon from '@/components/shared/DrinkIcon.vue'
import CoffeeIcon from '@/components/shared/CoffeeIcon.vue'
import BowlIcon from '@/components/shared/BowlIcon.vue'
import SnackIcon from '@/components/shared/SnackIcon.vue'

export interface MenuIconOption {
  key: string
  label: string
  shortLabel: string
  icon: Component
}

export const MENU_ICON_OPTIONS: MenuIconOption[] = [
  { key: 'icon:plate', label: 'Makanan / Piring', shortLabel: 'Makanan', icon: PlateIcon },
  { key: 'icon:drink', label: 'Minuman Dingin', shortLabel: 'Minuman', icon: DrinkIcon },
  { key: 'icon:coffee', label: 'Kopi / Teh Hangat', shortLabel: 'Kopi', icon: CoffeeIcon },
  { key: 'icon:bowl', label: 'Mangkok / Mie / Sup', shortLabel: 'Mangkok', icon: BowlIcon },
  { key: 'icon:snack', label: 'Cemilan / Kue / Roti', shortLabel: 'Cemilan', icon: SnackIcon },
]

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

/**
 * Resolves which icon component to use when a menu item does not have a real photo.
 * Falls back intelligently to inferred icon based on the item name if not explicitly set.
 */
export function resolveMenuIcon(img?: string | null, name?: string): MenuIconOption {
  if (img && img.startsWith('icon:')) {
    const found = MENU_ICON_OPTIONS.find((opt) => opt.key === img)
    if (found) return found
  }

  // Infer default icon from item name
  const n = (name || '').toLowerCase()
  if (n.includes('kopi') || n.includes('coffee') || n.includes('espresso') || n.includes('latte') || n.includes('cappuccino')) {
    return MENU_ICON_OPTIONS[2] // coffee
  }
  if (
    n.includes('es ') ||
    n.includes('jus') ||
    n.includes('juice') ||
    n.includes('teh') ||
    n.includes('tea') ||
    n.includes('drink') ||
    n.includes('soda') ||
    n.includes('boba') ||
    n.includes('milk') ||
    n.includes('susu')
  ) {
    return MENU_ICON_OPTIONS[1] // drink
  }
  if (
    n.includes('mie') ||
    n.includes('bakso') ||
    n.includes('soto') ||
    n.includes('sup') ||
    n.includes('soup') ||
    n.includes('ramen') ||
    n.includes('rawon') ||
    n.includes('bubur')
  ) {
    return MENU_ICON_OPTIONS[3] // bowl
  }
  if (
    n.includes('snack') ||
    n.includes('roti') ||
    n.includes('kue') ||
    n.includes('cake') ||
    n.includes('pisang') ||
    n.includes('keripik') ||
    n.includes('pastry') ||
    n.includes('donat')
  ) {
    return MENU_ICON_OPTIONS[4] // snack
  }

  return MENU_ICON_OPTIONS[0] // plate
}
