import type { Component } from 'vue'
import PlateIcon from '@/components/shared/PlateIcon.vue'
import ImageIcon from '@/components/shared/ImageIcon.vue'

/** Maps a lightbox/placeholder icon key to its icon component. */
export const PLACEHOLDER_ICONS: Record<string, Component> = {
  plate: PlateIcon,
  image: ImageIcon,
}
