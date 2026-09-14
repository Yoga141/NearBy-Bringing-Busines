import { ref } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch } from '@/lib/api'
import type { SocialVideo, VideoPlatform } from '@/types'

export interface VideoDraft {
  platform: VideoPlatform
  title: string
  url: string | null
  active?: boolean
}

export const useVideoStore = defineStore('videos', () => {
  // ---- Public (homepage section) ----
  const videos = ref<SocialVideo[]>([])
  const loading = ref(false)
  /** Cached after the first successful load so re-visiting the homepage is free. */
  const loaded = ref(false)

  async function fetchVideos(force = false) {
    if (loaded.value && !force) return
    loading.value = true
    try {
      videos.value = await apiFetch<SocialVideo[]>('/social-videos')
      loaded.value = true
    } catch {
      // A failed load leaves the section empty rather than breaking the page;
      // `loaded` stays false so the next visit retries.
      videos.value = []
    } finally {
      loading.value = false
    }
  }

  // ---- Admin (dashboard tab) ----
  const adminVideos = ref<SocialVideo[]>([])
  const adminLoading = ref(false)

  async function fetchAdminVideos() {
    adminLoading.value = true
    try {
      adminVideos.value = await apiFetch<SocialVideo[]>('/admin/social-videos')
    } finally {
      adminLoading.value = false
    }
  }

  async function createVideo(draft: VideoDraft) {
    const row = await apiFetch<SocialVideo>('/admin/social-videos', {
      method: 'POST',
      body: JSON.stringify(draft),
    })
    adminVideos.value = [...adminVideos.value, row]
    loaded.value = false
  }

  async function updateVideo(id: number, draft: Partial<VideoDraft>) {
    const row = await apiFetch<SocialVideo>(`/admin/social-videos/${id}`, {
      method: 'PUT',
      body: JSON.stringify(draft),
    })
    const idx = adminVideos.value.findIndex((v) => v.id === id)
    if (idx !== -1) adminVideos.value[idx] = row
    loaded.value = false
  }

  async function deleteVideo(id: number) {
    await apiFetch(`/admin/social-videos/${id}`, { method: 'DELETE' })
    adminVideos.value = adminVideos.value.filter((v) => v.id !== id)
    loaded.value = false
  }

  return {
    videos,
    loading,
    fetchVideos,
    adminVideos,
    adminLoading,
    fetchAdminVideos,
    createVideo,
    updateVideo,
    deleteVideo,
  }
})
