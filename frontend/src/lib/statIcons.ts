import type { Component } from 'vue'
import EyeIcon from '@/components/shared/EyeIcon.vue'
import StarIcon from '@/components/shared/StarIcon.vue'
import EditIcon from '@/components/shared/EditIcon.vue'
import HeartIcon from '@/components/shared/HeartIcon.vue'
import GridIcon from '@/components/shared/GridIcon.vue'
import TargetIcon from '@/components/shared/TargetIcon.vue'
import HourglassIcon from '@/components/shared/HourglassIcon.vue'

/** Maps a `StatCard.icon` key to its icon component. Used by dashboard/admin stat-card grids. */
export const STAT_ICONS: Record<string, Component> = {
  eye: EyeIcon,
  star: StarIcon,
  edit: EditIcon,
  heart: HeartIcon,
  grid: GridIcon,
  target: TargetIcon,
  hourglass: HourglassIcon,
}
