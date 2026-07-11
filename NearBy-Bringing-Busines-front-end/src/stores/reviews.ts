import { reactive } from 'vue'
import { defineStore } from 'pinia'
import { seedReviewsFor } from '@/data/reviews'
import type { Review } from '@/types'

export const useReviewsStore = defineStore('reviews', () => {
  const byUmkm = reactive(new Map<number, Review[]>())

  function reviewsFor(umkmId: number): Review[] {
    if (!byUmkm.has(umkmId)) {
      byUmkm.set(umkmId, seedReviewsFor(umkmId))
    }
    return byUmkm.get(umkmId)!
  }

  function addReview(umkmId: number, review: Omit<Review, 'id' | 'umkmId'>) {
    const list = reviewsFor(umkmId)
    list.unshift({ ...review, id: `${umkmId}-${Date.now()}`, umkmId })
  }

  return { reviewsFor, addReview }
})
