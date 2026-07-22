import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api, type Wrapped } from '@/lib/api'
import { FEATURED_IDS, DEFAULT_HIDDEN_UMKM } from '@/data/umkm'
import { CAT, CATEGORY_NAMES, REGION_CARDS } from '@/data/categories'
import type { Umkm, UmkmStatus } from '@/types'

export interface EnrichedUmkm extends Umkm {
  accent: string
  soft: string
  initial: string
}

function enrich(u: Umkm): EnrichedUmkm {
  const style = CAT[u.cat] ?? { accent: '#8A8578', soft: '#EEEADF', initial: '?' }
  return { ...u, accent: style.accent, soft: style.soft, initial: style.initial }
}

export const useUmkmStore = defineStore('umkm', () => {
  const all = ref<Umkm[]>([])
  const favorites = ref<number[]>([])

  const loaded = ref(false)
  const loading = ref(false)

  // Directory filter state (also seeded by home-page category/region clicks)
  const cat = ref('Semua')
  const loc = ref('Semua')
  const q = ref('')

  const enrichedAll = computed(() => all.value.map(enrich))

  function byId(id: number): EnrichedUmkm | undefined {
    return enrichedAll.value.find((u) => u.id === id)
  }

  /** Ambil daftar UMKM publik dari API (sekali, kecuali force). */
  async function loadAll(force = false) {
    if ((loaded.value || loading.value) && !force) return
    loading.value = true
    try {
      const res = await api.get<Wrapped<Umkm[]>>('/umkm')
      all.value = res.data
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  /** Ambil detail satu UMKM (items + ulasan) dan gabungkan ke daftar. */
  async function loadDetail(id: number) {
    const res = await api.get<Wrapped<Umkm>>(`/umkm/${id}`)
    const detail = res.data
    const idx = all.value.findIndex((u) => u.id === id)
    if (idx === -1) all.value.push(detail)
    else all.value[idx] = { ...all.value[idx], ...detail }
    if (detail.isFavorite && !favorites.value.includes(id)) favorites.value.push(id)
    return detail
  }

  const featured = computed(() =>
    FEATURED_IDS.map((id) => enrichedAll.value.find((u) => u.id === id)).filter(
      (u): u is EnrichedUmkm => !!u,
    ),
  )

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

  const favList = computed(() =>
    enrichedAll.value.filter((u) => favorites.value.includes(u.id)),
  )
  const favCount = computed(() => favorites.value.length)

  function isFavorite(id: number) {
    return favorites.value.includes(id)
  }

  /** Ambil daftar favorit milik user yang sedang login. */
  async function loadFavorites() {
    try {
      const res = await api.get<Wrapped<Umkm[]>>('/favorites')
      favorites.value = res.data.map((u) => u.id)
    } catch {
      // Tamu / belum login — abaikan.
    }
  }

  function clearFavorites() {
    favorites.value = []
  }

  /** Toggle favorit lewat API (butuh login). */
  async function toggleFavorite(id: number) {
    const res = await api.post<{ umkmId: number; isFavorite: boolean }>(`/umkm/${id}/favorite`)
    const idx = favorites.value.indexOf(id)
    if (res.isFavorite && idx === -1) favorites.value.push(id)
    else if (!res.isFavorite && idx !== -1) favorites.value.splice(idx, 1)
    return res.isFavorite
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

  // Admin-side visibility/lifecycle state
  const hiddenUmkm = ref<number[]>([...DEFAULT_HIDDEN_UMKM])
  /** Keyed by UMKM name (matches the prototype, which keys open/hour status by name). */
  const umkmStatus = ref<Record<string, UmkmStatus>>({})

  function statusOf(name: string): UmkmStatus {
    return umkmStatus.value[name] ?? 'Aktif'
  }

  return {
    all,
    loaded,
    loading,
    enrichedAll,
    byId,
    loadAll,
    loadDetail,
    favorites,
    favList,
    favCount,
    isFavorite,
    loadFavorites,
    clearFavorites,
    toggleFavorite,
    cat,
    loc,
    q,
    featured,
    categoryCards,
    regionCards,
    filteredDirectory,
    filterByCategory,
    filterByLocation,
    hiddenUmkm,
    umkmStatus,
    statusOf,
  }
})
