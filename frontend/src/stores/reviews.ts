import { reactive, ref } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, ApiError } from '@/lib/api'
import type { Review } from '@/types'

function fromApi(row: any): Review {
  return {
    id: String(row.id),
    umkmId: row.umkmId,
    umkmName: row.umkmName,
    userId: row.userId ?? null,
    initial: row.initial,
    name: row.name,
    stars: Number(row.stars) || 0,
    date: row.date ?? '',
    text: row.text ?? '',
    reply: row.reply ?? null,
  }
}

export const useReviewsStore = defineStore('reviews', () => {
  const byUmkm = reactive(new Map<number, Review[]>())
  const submitting = ref(false)
  const error = ref('')

  function reviewsFor(umkmId: number): Review[] {
    return byUmkm.get(umkmId) ?? []
  }

  /** Seed the cache for a UMKM (e.g. from the detail page's embedded `reviewsList`). */
  function setReviews(umkmId: number, list: Review[]) {
    byUmkm.set(umkmId, list)
  }

  async function fetchReviews(umkmId: number): Promise<Review[]> {
    const rows = await apiFetch<any[]>(`/umkm/${umkmId}/reviews`)
    const list = rows.map(fromApi)
    byUmkm.set(umkmId, list)
    return list
  }

  /** Replace (or add) one review in the cached list. */
  function upsert(umkmId: number, review: Review) {
    const list = reviewsFor(umkmId)
    const idx = list.findIndex((r) => r.id === review.id)
    byUmkm.set(umkmId, idx === -1 ? [review, ...list] : list.map((r) => (r.id === review.id ? review : r)))
  }

  /**
   * Post the user's review. The API allows one per UMKM: a 409 carries the
   * review that already exists, which is put in the list so the form can
   * switch to editing it instead of failing.
   */
  async function addReview(umkmId: number, payload: { stars: number; text: string }): Promise<boolean> {
    submitting.value = true
    error.value = ''
    try {
      const row = await apiFetch<any>(`/umkm/${umkmId}/reviews`, { method: 'POST', body: JSON.stringify(payload) })
      upsert(umkmId, fromApi(row))
      return true
    } catch (e) {
      if (e instanceof ApiError && e.status === 409) {
        const existing = (e.body as { review?: unknown } | undefined)?.review
        if (existing) upsert(umkmId, fromApi(existing))
      }
      error.value = e instanceof ApiError ? e.firstError : 'Gagal mengirim ulasan.'
      return false
    } finally {
      submitting.value = false
    }
  }

  async function updateReview(umkmId: number, reviewId: string, changes: { stars: number; text: string }) {
    const row = await apiFetch<any>(`/reviews/${reviewId}`, { method: 'PUT', body: JSON.stringify(changes) })
    upsert(umkmId, fromApi(row))
  }

  async function deleteReview(umkmId: number, reviewId: string) {
    await apiFetch(`/reviews/${reviewId}`, { method: 'DELETE' })
    const list = byUmkm.get(umkmId)
    if (list) byUmkm.set(umkmId, list.filter((r) => r.id !== reviewId))
  }

  /** UMKM owner replies to one of their reviews. */
  async function replyToReview(reviewId: string, reply: string) {
    return apiFetch<any>(`/reviews/${reviewId}/reply`, { method: 'POST', body: JSON.stringify({ reply }) }).then(fromApi)
  }

  return {
    submitting,
    error,
    reviewsFor,
    setReviews,
    fetchReviews,
    addReview,
    updateReview,
    deleteReview,
    replyToReview,
  }
})
