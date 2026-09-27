import { reactive, ref } from 'vue'
import { defineStore } from 'pinia'
import { ApiError, apiFetch } from '@/lib/api'
import type { AccountSession, Review } from '@/types'

function fromApiReview(row: any): Review {
  return {
    id: String(row.id),
    umkmId: row.umkmId,
    umkmName: row.umkmName,
    umkmCat: row.umkmCat,
    userId: row.userId ?? null,
    initial: row.initial,
    name: row.name,
    stars: Number(row.stars) || 0,
    date: row.date ?? '',
    text: row.text ?? '',
    reply: row.reply ?? null,
  }
}

export const useAccountStore = defineStore('account', () => {
  // Two-factor sign-in is not implemented; the Security tab shows it disabled.
  const security = reactive({ twofa: false })

  // ---- Ganti kata sandi ----

  const passwordSaving = ref(false)
  const passwordError = ref('')
  const passwordSaved = ref(false)

  /**
   * Change the password.
   *
   * The API signs every *other* device out on success, so the session list is
   * refetched - leaving it showing devices that no longer have access would be
   * worse than not listing them at all.
   */
  async function changePassword(current: string, next: string, confirmation: string): Promise<boolean> {
    passwordError.value = ''
    passwordSaved.value = false
    passwordSaving.value = true
    try {
      await apiFetch('/me/password', {
        method: 'PUT',
        body: JSON.stringify({
          current_password: current,
          password: next,
          password_confirmation: confirmation,
        }),
      })
      passwordSaved.value = true
      await fetchSessions(true)
      return true
    } catch (e) {
      passwordError.value =
        e instanceof ApiError ? e.firstError : 'Tidak dapat terhubung ke server. Coba lagi.'
      return false
    } finally {
      passwordSaving.value = false
    }
  }

  // ---- Sesi aktif ----

  const sessions = ref<AccountSession[]>([])
  const sessionsLoading = ref(false)
  const sessionsLoaded = ref(false)
  const sessionsError = ref('')

  async function fetchSessions(force = false) {
    if (sessionsLoaded.value && !force) return
    sessionsLoading.value = true
    sessionsError.value = ''
    try {
      sessions.value = await apiFetch<AccountSession[]>('/me/sessions')
      sessionsLoaded.value = true
    } catch (e) {
      sessionsError.value = e instanceof ApiError ? e.message : 'Gagal memuat daftar perangkat.'
      sessions.value = []
    } finally {
      sessionsLoading.value = false
    }
  }

  /** Sign one other device out. */
  async function revokeSession(id: number): Promise<boolean> {
    sessionsError.value = ''
    try {
      await apiFetch(`/me/sessions/${id}`, { method: 'DELETE' })
      sessions.value = sessions.value.filter((s) => s.id !== id)
      return true
    } catch (e) {
      sessionsError.value = e instanceof ApiError ? e.firstError : 'Gagal mengeluarkan perangkat.'
      return false
    }
  }

  // ---- Riwayat komentar (the user's own reviews, across every UMKM) ----
  const commentHistory = ref<Review[]>([])
  const commentHistoryLoading = ref(false)
  const commentHistoryLoaded = ref(false)

  async function fetchCommentHistory(force = false) {
    if (commentHistoryLoaded.value && !force) return
    commentHistoryLoading.value = true
    try {
      const rows = await apiFetch<any[]>('/me/reviews')
      commentHistory.value = rows.map(fromApiReview)
      commentHistoryLoaded.value = true
    } catch {
      // Leave whatever was already loaded; the tab shows an empty state either way.
    } finally {
      commentHistoryLoading.value = false
    }
  }

  async function deleteComment(id: string) {
    await apiFetch(`/reviews/${id}`, { method: 'DELETE' })
    commentHistory.value = commentHistory.value.filter((c) => c.id !== id)
  }
  async function updateComment(id: string, text: string) {
    const current = commentHistory.value.find((c) => c.id === id)
    if (!current) return
    const row = await apiFetch<any>(`/reviews/${id}`, {
      method: 'PUT',
      body: JSON.stringify({ stars: current.stars, text }),
    })
    const updated = fromApiReview(row)
    const idx = commentHistory.value.findIndex((c) => c.id === id)
    if (idx !== -1) commentHistory.value[idx] = { ...current, ...updated }
  }

  // ---- Hapus akun ----
  const deleting = ref(false)
  const deleteError = ref('')

  /** Soft-delete the signed-in account on the server (password required). */
  async function deleteMyAccount(password: string): Promise<boolean> {
    deleteError.value = ''
    deleting.value = true
    try {
      await apiFetch('/me', { method: 'DELETE', body: JSON.stringify({ password }) })
      return true
    } catch (e) {
      deleteError.value = e instanceof ApiError ? e.firstError : 'Tidak dapat terhubung ke server. Coba lagi.'
      return false
    } finally {
      deleting.value = false
    }
  }

  return {
    security,
    passwordSaving,
    passwordError,
    passwordSaved,
    changePassword,
    sessions,
    sessionsLoading,
    sessionsError,
    fetchSessions,
    revokeSession,
    commentHistory,
    commentHistoryLoading,
    fetchCommentHistory,
    deleteComment,
    updateComment,
    deleting,
    deleteError,
    deleteMyAccount,
  }
})
