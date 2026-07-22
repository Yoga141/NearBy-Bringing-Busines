import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { api, setToken, getToken, type Wrapped } from '@/lib/api'
import type { AuthUser, Role } from '@/types'

const USER_KEY = 'nearby_user'

/** Respons endpoint /login dan /register. */
interface AuthResponse {
  user: AuthUser
  token: string
}

/** Kredensial akun demo (tombol "masuk cepat sebagai" di halaman login). */
const DEMO_CREDENTIALS: Record<Role, { email: string; password: string }> = {
  user: { email: 'rizky.p@mail.com', password: 'password' },
  owner: { email: 'dewi.umkm@mail.com', password: 'password' },
  admin: { email: 'admin@nearby.id', password: 'password' },
}

function loadStoredUser(): AuthUser | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? (JSON.parse(raw) as AuthUser) : null
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  // Diisi dari localStorage agar sesi bertahan setelah refresh (divalidasi
  // ulang lewat restoreSession() saat aplikasi mount).
  const user = ref<AuthUser | null>(loadStoredUser())
  const regRole = ref<Role>('user')

  const isGuest = computed(() => !user.value)
  const isAuthed = computed(() => !!user.value)
  const isOwner = computed(() => user.value?.role === 'owner')
  const isAdmin = computed(() => user.value?.role === 'admin')

  const authInitial = computed(
    () => user.value?.initial ?? user.value?.name.trim().charAt(0).toUpperCase() ?? '',
  )
  const authFirst = computed(() => user.value?.name.split(' ')[0] ?? '')
  const authRoleLabel = computed(() => {
    if (user.value?.roleLabel) return user.value.roleLabel
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

  // Field halaman profil, terpisah dari sesi supaya bisa diedit bebas.
  const profileName = ref('')
  const profileEmail = ref('')
  const profilePhone = ref('')

  function applySession(payload: AuthUser, token?: string) {
    user.value = payload
    localStorage.setItem(USER_KEY, JSON.stringify(payload))
    if (token) setToken(token)
    profileName.value = payload.name
    profileEmail.value = payload.email ?? ''
    profilePhone.value = payload.phone ?? ''
  }

  function clearSession() {
    user.value = null
    localStorage.removeItem(USER_KEY)
    setToken(null)
    profileName.value = ''
    profileEmail.value = ''
    profilePhone.value = ''
  }

  /** Login sungguhan ke backend. Lempar ApiError kalau gagal. */
  async function login(email: string, password: string) {
    const res = await api.post<AuthResponse>('/login', { email, password })
    applySession(res.user, res.token)
    return res.user
  }

  /** Login cepat pakai salah satu akun demo (semua password: "password"). */
  async function loginAsRole(role: Role) {
    const { email, password } = DEMO_CREDENTIALS[role]
    return login(email, password)
  }
  const loginAsUser = () => loginAsRole('user')
  const loginAsOwner = () => loginAsRole('owner')
  const loginAsAdmin = () => loginAsRole('admin')

  interface RegisterInput {
    name: string
    email: string
    password: string
    phone?: string
    role: Role
  }

  async function register(input: RegisterInput) {
    const res = await api.post<AuthResponse>('/register', {
      name: input.name,
      email: input.email,
      password: input.password,
      phone: input.phone ?? null,
      role: input.role,
    })
    applySession(res.user, res.token)
    return res.user
  }

  /** Validasi ulang token yang tersimpan; dipanggil sekali saat aplikasi mount. */
  async function restoreSession() {
    if (!getToken()) return
    try {
      const res = await api.get<Wrapped<AuthUser>>('/me')
      applySession(res.data)
    } catch {
      clearSession()
    }
  }

  async function logout() {
    if (getToken()) {
      try {
        await api.post('/logout')
      } catch {
        // Abaikan — sesi tetap dibersihkan di sisi klien.
      }
    }
    clearSession()
  }

  /**
   * Restore sesi lokal tanpa autentikasi ulang — dipakai fitur "batal hapus
   * akun" (RestoreBanner), yang murni state klien.
   */
  function restoreLocalSession(name: string, role: Role) {
    applySession({ name, role })
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
    login,
    loginAsRole,
    loginAsUser,
    loginAsOwner,
    loginAsAdmin,
    register,
    restoreSession,
    logout,
    restoreLocalSession,
  }
})
