import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import type { AuthUser, Role } from '@/types'
import { apiFetch, ApiError, getToken, setToken } from '@/lib/api'
import { useUmkmStore } from './umkm'

/** Shape returned by UserResource on the Laravel side. */
interface ApiUser {
  id: number
  name: string
  email: string
  phone: string | null
  role: Role
  status: string
  avatarUrl: string | null
}

interface AuthResponse {
  user: ApiUser
  token: string
}

export interface RegisterPayload {
  name: string
  email: string
  phone?: string
  password: string
  role: Role
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const regRole = ref<Role>('user')

  /** Set by login()/register() so the form can show why it failed. */
  const authError = ref('')
  const authLoading = ref(false)

  const isGuest = computed(() => !user.value)
  const isAuthed = computed(() => !!user.value)
  const isOwner = computed(() => user.value?.role === 'owner')
  const isAdmin = computed(() => user.value?.role === 'admin')

  const authInitial = computed(() => user.value?.name.trim().charAt(0).toUpperCase() ?? '')
  const authFirst = computed(() => user.value?.name.split(' ')[0] ?? '')
  const authRoleLabel = computed(() => {
    switch (user.value?.role) {
      case 'owner':
        return 'Pemilik UMKM'
      case 'admin':
        return 'Administrator'
      case 'user':
        return 'Pengguna'
      default:
        return ''
    }
  })

  // Profile-settings fields, independent of the session user so they can be
  // edited freely in the settings form. Seeded from the real account on
  // login/register/session-restore.
  const profileName = ref('')
  const profileEmail = ref('')
  const profilePhone = ref('')

  function applyUser(apiUser: ApiUser) {
    user.value = {
      id: apiUser.id,
      name: apiUser.name,
      email: apiUser.email,
      phone: apiUser.phone,
      role: apiUser.role,
      status: apiUser.status,
      avatarUrl: apiUser.avatarUrl ?? null,
    }
    profileName.value = apiUser.name
    profileEmail.value = apiUser.email
    profilePhone.value = apiUser.phone ?? ''
    // Fire-and-forget: pull in this account's favorites now that we know who's signed in.
    useUmkmStore().fetchFavorites()
  }

  function messageFor(error: unknown): string {
    if (error instanceof ApiError) return error.firstError
    return 'Tidak dapat terhubung ke server. Periksa koneksi internetmu.'
  }

  /** Log in against the backend and store the returned Sanctum token. */
  async function login(email: string, password: string): Promise<boolean> {
    authError.value = ''
    authLoading.value = true
    try {
      const res = await apiFetch<AuthResponse>('/login', {
        method: 'POST',
        body: JSON.stringify({ email, password }),
      })
      setToken(res.token)
      applyUser(res.user)
      return true
    } catch (error) {
      authError.value = messageFor(error)
      return false
    } finally {
      authLoading.value = false
    }
  }

  /** Create a new account (role: user or owner) and sign in immediately. */
  async function register(payload: RegisterPayload): Promise<boolean> {
    authError.value = ''
    authLoading.value = true
    try {
      const res = await apiFetch<AuthResponse>('/register', {
        method: 'POST',
        body: JSON.stringify(payload),
      })
      setToken(res.token)
      applyUser(res.user)
      return true
    } catch (error) {
      authError.value = messageFor(error)
      return false
    } finally {
      authLoading.value = false
    }
  }

  // ---- Foto profil ----

  const photoUploading = ref(false)
  const photoError = ref('')

  /**
   * Upload (or replace) the profile photo.
   *
   * The API answers with the whole updated user, so `applyUser` refreshes every
   * avatar in the app at once — header, dashboard, settings modal — instead of
   * each one having to be told about the new URL.
   */
  async function uploadPhoto(file: File): Promise<boolean> {
    photoError.value = ''
    photoUploading.value = true
    try {
      const form = new FormData()
      form.append('photo', file)
      // No Content-Type header here on purpose: the browser has to set the
      // multipart boundary itself, and apiFetch already skips it for FormData.
      const updated = await apiFetch<ApiUser>('/me/photo', { method: 'POST', body: form })
      applyUser(updated)
      return true
    } catch (error) {
      photoError.value = messageFor(error)
      return false
    } finally {
      photoUploading.value = false
    }
  }

  /** Remove the photo and go back to the initial avatar. */
  async function removePhoto(): Promise<boolean> {
    photoError.value = ''
    photoUploading.value = true
    try {
      const updated = await apiFetch<ApiUser>('/me/photo', { method: 'DELETE' })
      applyUser(updated)
      return true
    } catch (error) {
      photoError.value = messageFor(error)
      return false
    } finally {
      photoUploading.value = false
    }
  }

  /**
   * Restore a session locally without calling the API. Used only by the
   * account-deletion "undo" banner: deleteMyAccount()/restoreMyAccount() in
   * the account store are a local-only prototype (not backed by a real
   * delete-account endpoint yet), so this mirrors that by staying local too.
   */
  function resumeLocalSession(name: string, role: Role) {
    user.value = {
      id: user.value?.id ?? 0,
      name,
      email: profileEmail.value,
      phone: profilePhone.value || null,
      role,
      status: 'aktif',
      avatarUrl: user.value?.avatarUrl ?? null,
    }
    profileName.value = name
  }

  /** Clear local session state immediately, then best-effort revoke the token. */
  function logout() {
    const activeToken = getToken()
    user.value = null
    setToken(null)
    useUmkmStore().resetFavorites()
    if (activeToken) {
      fetch('/api/logout', {
        method: 'POST',
        headers: { Accept: 'application/json', Authorization: `Bearer ${activeToken}` },
      }).catch(() => {
        // Best-effort: the local session is already cleared either way.
      })
    }
  }

  /**
   * Restore the session from a previously stored token (e.g. after a page
   * reload). Cached so repeated calls (from route guards, layout mounts,
   * etc.) only hit the API once.
   */
  let restorePromise: Promise<void> | null = null
  function restoreSession(): Promise<void> {
    if (!restorePromise) {
      restorePromise = (async () => {
        if (!getToken()) return
        try {
          const me = await apiFetch<ApiUser>('/me')
          applyUser(me)
        } catch {
          setToken(null)
          user.value = null
        }
      })()
    }
    return restorePromise
  }

  return {
    user,
    regRole,
    isGuest,
    isAuthed,
    isOwner,
    isAdmin,
    authInitial,
    authFirst,
    authRoleLabel,
    profileName,
    profileEmail,
    profilePhone,
    authError,
    authLoading,
    photoUploading,
    photoError,
    uploadPhoto,
    removePhoto,
    login,
    register,
    logout,
    restoreSession,
    resumeLocalSession,
  }
})
