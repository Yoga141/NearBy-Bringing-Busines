import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, ApiError } from '@/lib/api'
import { CAT, CATEGORY_NAMES, REGION_CARDS } from '@/data/categories'
import type { CategoryName, LocationName, Review, Umkm, UmkmStatus } from '@/types'

export interface EnrichedUmkm extends Umkm {
  accent: string
  soft: string
  initial: string
}

export interface UmkmDetail extends EnrichedUmkm {
  reviewsList: Review[]
  isFavorite: boolean
}

const STATUS_FROM_API: Record<string, UmkmStatus> = { aktif: 'Aktif', libur: 'Libur', tutup: 'Tutup' }
const STATUS_TO_API: Record<UmkmStatus, string> = { Aktif: 'aktif', Libur: 'libur', Tutup: 'tutup' }

/** Maps a raw Laravel UmkmResource row onto the frontend `Umkm` shape. Exported for the dashboard store, which fetches UMKM rows from the owner/admin endpoints directly. */
export function umkmFromApi(row: any): Umkm {
  return {
    id: row.id,
    ownerId: row.ownerId ?? null,
    name: row.name,
    cat: row.cat,
    loc: row.loc,
    rating: Number(row.rating) || 0,
    reviews: Number(row.reviews) || 0,
    priceLabel: row.priceLabel ?? '',
    tag: row.tag ?? '',
    imgLabel: row.imgLabel ?? '',
    address: row.address ?? '',
    hours: row.hours ?? '',
    phone: row.phone ?? '',
    ig: row.ig ?? '',
    listLabel: row.listLabel ?? '',
    items: (row.items ?? []).map((it: any) => ({
      name: it.name,
      price: it.price ?? '',
      img: it.img ?? undefined,
      avail: it.avail !== false,
    })),
    status: STATUS_FROM_API[row.status] ?? 'Aktif',
    verification: row.verification ?? 'disetujui',
    hidden: !!row.hidden,
    views: Number(row.views) || 0,
    deletedAt: row.deletedAt ?? null,
  }
}

function fromApiDetail(row: any): UmkmDetail {
  const base = umkmFromApi(row)
  return {
    ...base,
    ...enrich(base),
    reviewsList: (row.reviewsList ?? []).map(fromApiReview),
    isFavorite: !!row.isFavorite,
  }
}

function fromApiReview(row: any): Review {
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

function enrich(u: Umkm): { accent: string; soft: string; initial: string } {
  const style = CAT[u.cat as CategoryName]
  return { accent: style.accent, soft: style.soft, initial: style.initial }
}

/** Shared by every store that needs to render a raw API UMKM row (dashboard.ts included). */
export function enrichUmkm(u: Umkm): EnrichedUmkm {
  return { ...u, ...enrich(u) }
}

export const useUmkmStore = defineStore('umkm', () => {
  const all = ref<Umkm[]>([])
  const loading = ref(false)
  const error = ref('')
  const loaded = ref(false)

  const favorites = ref<number[]>([])
  const favoritesLoaded = ref(false)

  // Directory filter state (also seeded by home-page category/region clicks)
  const cat = ref('Semua')
  const loc = ref('Semua')
  const q = ref('')

  const enrichedAll = computed(() => all.value.map(enrichUmkm))

  function byId(id: number): EnrichedUmkm | undefined {
    return enrichedAll.value.find((u) => u.id === id)
  }

  /** Homepage "UMKM unggulan": top 4 by rating (there's no curated-pick concept in the API). */
  const featured = computed(() => [...enrichedAll.value].sort((a, b) => b.rating - a.rating).slice(0, 4))

  const categoryCards = computed(() =>
    CATEGORY_NAMES.map((name) => ({
      name,
      ...CAT[name],
      count: all.value.filter((u) => u.cat === name).length,
    })),
  )

  const regionCards = computed(() =>
    REGION_CARDS.map(([full, short]) => ({
      full,
      short,
      count: all.value.filter((u) => u.loc === full).length,
    })),
  )

  const filteredDirectory = computed(() => {
    const query = q.value.trim().toLowerCase()
    return enrichedAll.value
      .filter((u) => cat.value === 'Semua' || u.cat === cat.value)
      .filter((u) => loc.value === 'Semua' || u.loc === loc.value)
      .filter((u) => {
        if (!query) return true
        return `${u.name} ${u.tag} ${u.cat}`.toLowerCase().includes(query)
      })
      .sort((a, b) => b.rating - a.rating)
  })

  const favList = computed(() => enrichedAll.value.filter((u) => favorites.value.includes(u.id)))
  const favCount = computed(() => favorites.value.length)

  function isFavorite(id: number) {
    return favorites.value.includes(id)
  }

  function filterByCategory(name: string) {
    cat.value = name
    loc.value = 'Semua'
    q.value = ''
  }

  function filterByLocation(full: string) {
    loc.value = full
    cat.value = 'Semua'
    q.value = ''
  }

  /** Load the public catalog (approved, non-hidden UMKM). Cached — pass force to refetch. */
  async function fetchAll(force = false) {
    if (loaded.value && !force) return
    loading.value = true
    error.value = ''
    try {
      const rows = await apiFetch<any[]>('/umkm')
      all.value = rows.map(umkmFromApi)
      loaded.value = true
    } catch (e) {
      error.value = e instanceof ApiError ? e.message : 'Gagal memuat data UMKM.'
    } finally {
      loading.value = false
    }
  }

  const detailLoading = ref(false)
  const detailError = ref('')

  /** Full detail (items, reviews, isFavorite) for the detail page. Not cached — always fresh. */
  async function fetchDetail(id: number): Promise<UmkmDetail | null> {
    detailLoading.value = true
    detailError.value = ''
    try {
      const row = await apiFetch<any>(`/umkm/${id}`)
      const detail = fromApiDetail(row)
      const idx = all.value.findIndex((u) => u.id === id)
      if (idx !== -1) all.value[idx] = { ...all.value[idx], rating: detail.rating, reviews: detail.reviews, status: detail.status, views: detail.views }
      return detail
    } catch (e) {
      detailError.value = e instanceof ApiError ? e.message : 'Gagal memuat detail UMKM.'
      return null
    } finally {
      detailLoading.value = false
    }
  }

  /** Load the signed-in user's favorites. No-op (leaves the list empty) for guests. */
  async function fetchFavorites() {
    try {
      const rows = await apiFetch<any[]>('/favorites')
      favorites.value = rows.map((r: any) => r.id)
      favoritesLoaded.value = true
    } catch {
      favorites.value = []
    }
  }

  function resetFavorites() {
    favorites.value = []
    favoritesLoaded.value = false
  }

  /** Optimistically toggle, reconciling with (or reverting to) the server's answer. */
  async function toggleFavorite(id: number) {
    const had = favorites.value.includes(id)
    favorites.value = had ? favorites.value.filter((x) => x !== id) : [...favorites.value, id]
    try {
      const res = await apiFetch<{ umkmId: number; isFavorite: boolean }>(`/umkm/${id}/favorite`, { method: 'POST' })
      const nowHas = favorites.value.includes(id)
      if (res.isFavorite && !nowHas) favorites.value.push(id)
      if (!res.isFavorite && nowHas) favorites.value = favorites.value.filter((x) => x !== id)
    } catch {
      favorites.value = had ? [...favorites.value, id] : favorites.value.filter((x) => x !== id)
    }
  }

  interface UmkmPayload {
    name: string
    category: CategoryName
    location: LocationName
    price_label?: string
    tag?: string
    img_label?: string
    address?: string
    hours?: string
    phone?: string
    ig?: string
    list_label?: string
    status?: string
    items?: { name: string; price?: string; img?: string; available?: boolean }[]
  }

  /** Owner submits a new UMKM (pending verification until an admin approves it). */
  async function createUmkm(payload: UmkmPayload): Promise<Umkm> {
    const row = await apiFetch<any>('/umkm', { method: 'POST', body: JSON.stringify(payload) })
    return umkmFromApi(row)
  }

  /** Owner edits one of their own UMKM. */
  async function updateUmkm(id: number, payload: Partial<UmkmPayload>): Promise<Umkm> {
    const row = await apiFetch<any>(`/umkm/${id}`, { method: 'PUT', body: JSON.stringify(payload) })
    const updated = umkmFromApi(row)
    const idx = all.value.findIndex((u) => u.id === id)
    if (idx !== -1) all.value[idx] = updated
    return updated
  }

  /** Owner sets their UMKM's open/on-leave/closed status. */
  function setUmkmStatus(id: number, status: UmkmStatus) {
    return updateUmkm(id, { status: STATUS_TO_API[status] })
  }

  /** Soft-delete (moves to the owner's/admin's trash). */
  async function deleteUmkm(id: number) {
    await apiFetch(`/umkm/${id}`, { method: 'DELETE' })
    all.value = all.value.filter((u) => u.id !== id)
  }

  return {
    all,
    loading,
    error,
    loaded,
    enrichedAll,
    byId,
    favorites,
    favoritesLoaded,
    favList,
    favCount,
    isFavorite,
    toggleFavorite,
    fetchFavorites,
    resetFavorites,
    cat,
    loc,
    q,
    featured,
    categoryCards,
    regionCards,
    filteredDirectory,
    filterByCategory,
    filterByLocation,
    fetchAll,
    fetchDetail,
    detailLoading,
    detailError,
    createUmkm,
    updateUmkm,
    setUmkmStatus,
    deleteUmkm,
  }
})
