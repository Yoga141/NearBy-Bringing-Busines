import { computed, reactive, ref } from 'vue'
import { defineStore } from 'pinia'
import { ApiError, apiFetch } from '@/lib/api'
import type { AccountSession, Review, Role } from '@/types'

export interface DeletedAccount {
  name: string
  email: string
  role: string
  rawRole: Role
  initial: string
  deletedAt: number
}

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
  const perms = reactive({
    emailNotif: true,
    pushNotif: false,
    location: true,
    promo: false,
    dataShare: true,
  })
  const security = reactive({ twofa: false })

  function togglePerm(key: keyof typeof perms) {
    perms[key] = !perms[key]
  }
  function toggleSecurity(key: keyof typeof security) {
    security[key] = !security[key]
  }

  // ---- Ganti kata sandi ----

  const passwordSaving = ref(false)
  const passwordError = ref('')
  const passwordSaved = ref(false)

  /**
   * Change the password.
   *
   * The API signs every *other* device out on success, so the session list is
   * refetched — leaving it showing devices that no longer have access would be
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

  const deletedAccounts = ref<DeletedAccount[]>([
    {
      name: 'Andini Putri',
      email: 'andini.p@mail.com',
      role: 'Pengguna',
      rawRole: 'user',
      initial: 'A',
      deletedAt: Date.now() - 5 * 86400000,
    },
  ])
  const myDeleted = ref<DeletedAccount | null>(null)

  const myDeletedDaysLeft = computed(() =>
    myDeleted.value ? Math.max(0, 30 - Math.floor((Date.now() - myDeleted.value.deletedAt) / 86400000)) : 30,
  )

  function deleteMyAccount(name: string, email: string, role: Role, roleLabel: string, initial: string) {
    const acc: DeletedAccount = { name, email, role: roleLabel, rawRole: role, initial, deletedAt: Date.now() }
    deletedAccounts.value.push(acc)
    myDeleted.value = acc
  }

  /** Returns the {name, role} to restore into the auth session, or null if nothing to restore. */
  function restoreMyAccount(): { name: string; role: Role } | null {
    const m = myDeleted.value
    if (!m) return null
    deletedAccounts.value = deletedAccounts.value.filter((x) => x !== m)
    myDeleted.value = null
    return { name: m.name, role: m.rawRole }
  }

  return {
    perms,
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
    togglePerm,
    toggleSecurity,
    commentHistory,
    commentHistoryLoading,
    fetchCommentHistory,
    deleteComment,
    updateComment,
    deletedAccounts,
    myDeleted,
    myDeletedDaysLeft,
    deleteMyAccount,
    restoreMyAccount,
  }
})
