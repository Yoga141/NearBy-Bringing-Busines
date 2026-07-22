import { reactive } from 'vue'
import { defineStore } from 'pinia'
import { api, type Wrapped } from '@/lib/api'
import type { Review } from '@/types'

export const useReviewsStore = defineStore('reviews', () => {
  const byUmkm = reactive(new Map<number, Review[]>())

  /** Daftar ulasan yang sudah dimuat untuk sebuah UMKM (kosong sebelum loadFor). */
  function reviewsFor(umkmId: number): Review[] {
    if (!byUmkm.has(umkmId)) byUmkm.set(umkmId, [])
    return byUmkm.get(umkmId)!
  }

  /** Ambil ulasan sebuah UMKM dari API. */
  async function loadFor(umkmId: number) {
    const res = await api.get<Wrapped<Review[]>>(`/umkm/${umkmId}/reviews`)
    byUmkm.set(umkmId, res.data)
    return res.data
  }

  /** Kirim ulasan baru (butuh login). */
  async function addReview(umkmId: number, input: { stars: number; text: string }) {
    const res = await api.post<Wrapped<Review>>(`/umkm/${umkmId}/reviews`, {
      stars: input.stars,
      text: input.text,
    })
    reviewsFor(umkmId).unshift(res.data)
    return res.data
  }

  // Catatan: backend belum punya endpoint edit/hapus ulasan oleh pengguna,
  // jadi kedua aksi ini hanya mengubah cache lokal (tidak tersimpan permanen).
  function updateReview(umkmId: number, reviewId: string, changes: { stars: number; text: string }) {
    const rv = reviewsFor(umkmId).find((r) => r.id === reviewId)
    if (rv) {
      rv.stars = changes.stars
      rv.text = changes.text
    }
  }

  function deleteReview(umkmId: number, reviewId: string) {
    const list = reviewsFor(umkmId)
    const idx = list.findIndex((r) => r.id === reviewId)
    if (idx !== -1) list.splice(idx, 1)
  }

  return { reviewsFor, loadFor, addReview, updateReview, deleteReview }
})
