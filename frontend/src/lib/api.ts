/**
 * Thin fetch wrapper for the Laravel API.
 *
 * Requests go to `/api/*`. In dev, Vite proxies that path to the local
 * backend (see vite.config.ts); in production the built SPA and the API
 * are deployed under the same domain (see .cpanel.yml), so `/api/*` is
 * same-origin there too. Auth uses Sanctum personal access tokens sent as
 * a Bearer header, persisted in localStorage between page loads.
 */

const TOKEN_KEY = 'nearby_token'

export function getToken(): string | null {
  try {
    return localStorage.getItem(TOKEN_KEY)
  } catch {
    return null
  }
}

export function setToken(token: string | null) {
  try {
    if (token) localStorage.setItem(TOKEN_KEY, token)
    else localStorage.removeItem(TOKEN_KEY)
  } catch {
    // localStorage can be unavailable (private mode, blocked storage); the
    // session simply won't survive a page reload in that case.
  }
}

/** A failed API response, carrying Laravel's message and (if a 422) field errors. */
export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }

  /** First validation message, falling back to the top-level message. */
  get firstError(): string {
    if (this.errors) {
      const first = Object.values(this.errors)[0]?.[0]
      if (first) return first
    }
    return this.message
  }
}

export async function apiFetch<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')
  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }
  const token = getToken()
  if (token) headers.set('Authorization', `Bearer ${token}`)

  let res: Response
  try {
    res = await fetch(`/api${path}`, { ...options, headers })
  } catch {
    throw new ApiError('Tidak dapat terhubung ke server. Periksa koneksi internetmu.', 0)
  }

  const contentType = res.headers.get('content-type') ?? ''
  const payload = contentType.includes('application/json') ? await res.json().catch(() => null) : null

  if (!res.ok) {
    const message = (payload as { message?: string } | null)?.message ?? 'Terjadi kesalahan. Silakan coba lagi.'
    const errors = (payload as { errors?: Record<string, string[]> } | null)?.errors
    throw new ApiError(message, res.status, errors)
  }

  return payload as T
}
