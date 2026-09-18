import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { ApiError, apiFetch, getToken } from '@/lib/api'
import type { GuideVideo, GuideVideoLimits } from '@/types'

export interface GuideVideoDraft {
  title: string
  description?: string | null
  active?: boolean
}

/**
 * The registration tutorial video shown on /panduan: uploaded by an admin from
 * the dashboard, streamed back from our own storage.
 *
 * Distinct from `videos.ts` (social media *links* on the homepage) - this one
 * owns an actual file, which is why the upload goes through XMLHttpRequest
 * rather than `apiFetch`.
 */
export const useGuideVideoStore = defineStore('guideVideo', () => {
  // ---- Public (halaman panduan) ----
  const video = ref<GuideVideo | null>(null)
  const loading = ref(false)
  /** Cached after the first load; a 204 (no video yet) counts as loaded too. */
  const loaded = ref(false)

  async function fetchVideo(force = false) {
    if (loaded.value && !force) return
    loading.value = true
    try {
      // 204 when no video is live yet - apiFetch gives back null for that.
      video.value = (await apiFetch<GuideVideo | null>('/guide-video')) ?? null
      loaded.value = true
    } catch {
      // A failed load leaves the guide page on its placeholder rather than
      // breaking it; `loaded` stays false so the next visit retries.
      video.value = null
    } finally {
      loading.value = false
    }
  }

  // ---- Admin (tab dashboard) ----
  const adminVideos = ref<GuideVideo[]>([])
  const adminLoading = ref(false)
  const limits = ref<GuideVideoLimits | null>(null)

  /** 0–100 while an upload is in flight, null when idle. */
  const uploadProgress = ref<number | null>(null)
  const uploading = computed(() => uploadProgress.value !== null)

  async function fetchAdminVideos() {
    adminLoading.value = true
    try {
      adminVideos.value = await apiFetch<GuideVideo[]>('/admin/guide-videos')
    } finally {
      adminLoading.value = false
    }
  }

  async function fetchLimits() {
    try {
      limits.value = await apiFetch<GuideVideoLimits>('/admin/guide-videos/limits')
    } catch {
      // Not fatal: the form just can't state the ceiling up front.
      limits.value = null
    }
  }

  /**
   * Upload a new tutorial video.
   *
   * XMLHttpRequest, not fetch: a video takes long enough that an admin needs a
   * progress bar, and `fetch` still exposes no upload progress. The error paths
   * are mapped onto `ApiError` so callers handle failures the same way they do
   * for every other endpoint.
   */
  function upload(file: File, draft: GuideVideoDraft): Promise<GuideVideo> {
    const form = new FormData()
    form.append('video', file)
    form.append('title', draft.title)
    if (draft.description) form.append('description', draft.description)

    return new Promise<GuideVideo>((resolve, reject) => {
      const xhr = new XMLHttpRequest()
      xhr.open('POST', '/api/admin/guide-videos')
      xhr.setRequestHeader('Accept', 'application/json')
      const token = getToken()
      if (token) xhr.setRequestHeader('Authorization', `Bearer ${token}`)

      uploadProgress.value = 0
      xhr.upload.onprogress = (event) => {
        if (event.lengthComputable) {
          uploadProgress.value = Math.round((event.loaded / event.total) * 100)
        }
      }

      const fail = (message: string, status: number, errors?: Record<string, string[]>) => {
        uploadProgress.value = null
        reject(new ApiError(message, status, errors))
      }

      xhr.onload = () => {
        uploadProgress.value = null
        let payload: { message?: string; errors?: Record<string, string[]> } | null = null
        try {
          payload = JSON.parse(xhr.responseText)
        } catch {
          payload = null
        }

        if (xhr.status >= 200 && xhr.status < 300) {
          const row = payload as unknown as GuideVideo
          // A new upload becomes the live one; the API deactivates the rest.
          adminVideos.value = [row, ...adminVideos.value.map((v) => ({ ...v, active: false }))]
          video.value = row
          loaded.value = true
          resolve(row)
          return
        }

        reject(
          new ApiError(
            payload?.message ?? 'Gagal mengunggah video. Coba lagi.',
            xhr.status,
            payload?.errors,
          ),
        )
      }

      xhr.onerror = () => fail('Tidak dapat terhubung ke server saat mengunggah.', 0)
      xhr.onabort = () => fail('Unggahan dibatalkan.', 0)

      xhr.send(form)
    })
  }

  async function updateVideo(id: number, draft: Partial<GuideVideoDraft>) {
    const row = await apiFetch<GuideVideo>(`/admin/guide-videos/${id}`, {
      method: 'PUT',
      body: JSON.stringify(draft),
    })
    adminVideos.value = adminVideos.value.map((v) => {
      if (v.id === id) return row
      // Only one video is live at a time, so activating this one switches the
      // others off in the list exactly as the API just did in the database.
      return row.active ? { ...v, active: false } : v
    })
    loaded.value = false
    return row
  }

  async function deleteVideo(id: number) {
    await apiFetch(`/admin/guide-videos/${id}`, { method: 'DELETE' })
    adminVideos.value = adminVideos.value.filter((v) => v.id !== id)
    if (video.value?.id === id) video.value = null
    loaded.value = false
  }

  return {
    video,
    loading,
    fetchVideo,
    adminVideos,
    adminLoading,
    fetchAdminVideos,
    limits,
    fetchLimits,
    uploadProgress,
    uploading,
    upload,
    updateVideo,
    deleteVideo,
  }
})
