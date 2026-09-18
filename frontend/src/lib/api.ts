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

/** Sends the request with the JSON `Accept` header and the bearer token; throws ApiError on network failure. */
async function send(path: string, options: RequestInit): Promise<Response> {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')
  if (options.body && !(options.body instanceof FormData)) {
    headers.set('Content-Type', 'application/json')
  }
  const token = getToken()
  if (token) headers.set('Authorization', `Bearer ${token}`)

  try {
    return await fetch(`/api${path}`, { ...options, headers })
  } catch {
    throw new ApiError('Tidak dapat terhubung ke server. Periksa koneksi internetmu.', 0)
  }
}

async function readJson(res: Response): Promise<unknown> {
  const contentType = res.headers.get('content-type') ?? ''
  return contentType.includes('application/json') ? await res.json().catch(() => null) : null
}

function toApiError(res: Response, payload: unknown): ApiError {
  const body = payload as { message?: string; errors?: Record<string, string[]> } | null
  return new ApiError(body?.message ?? 'Terjadi kesalahan. Silakan coba lagi.', res.status, body?.errors)
}

export async function apiFetch<T = unknown>(path: string, options: RequestInit = {}): Promise<T> {
  const res = await send(path, options)
  const payload = await readJson(res)
  if (!res.ok) throw toApiError(res, payload)
  return payload as T
}

/** File name from a Content-Disposition header, preferring the UTF-8 `filename*` form. */
function filenameFrom(header: string | null): string | null {
  if (!header) return null
  const star = /filename\*=(?:UTF-8'')?([^;]+)/i.exec(header)
  if (star?.[1]) {
    try {
      return decodeURIComponent(star[1].trim().replace(/^"|"$/g, ''))
    } catch {
      // Malformed encoding - fall through to the plain form.
    }
  }
  return /filename="?([^";]+)"?/i.exec(header)?.[1] ?? null
}

/**
 * Downloads a file the API generates (e.g. an Excel export) and hands it to
 * the browser's save dialog. A plain `<a href>` can't be used for this: the
 * endpoints need the bearer token, which only a fetch can send.
 */
export async function apiDownload(path: string, fallbackName: string): Promise<void> {
  const res = await send(path, { method: 'GET' })
  if (!res.ok) throw toApiError(res, await readJson(res))

  const blob = await res.blob()
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = filenameFrom(res.headers.get('content-disposition')) ?? fallbackName
  document.body.appendChild(a)
  a.click()
  a.remove()
  // Revoke on the next tick so the download has certainly started.
  setTimeout(() => URL.revokeObjectURL(url), 0)
}
