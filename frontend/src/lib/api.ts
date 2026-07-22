// Tipis-tipis wrapper fetch untuk backend Laravel (Sanctum token auth).
// Base URL diambil dari VITE_API_URL (lihat frontend/.env). Token disimpan di
// localStorage supaya sesi tetap ada setelah refresh halaman.

const BASE = (import.meta.env.VITE_API_URL as string | undefined) ?? 'http://127.0.0.1:8000/api'

const TOKEN_KEY = 'nearby_token'

let token: string | null = localStorage.getItem(TOKEN_KEY)

export function setToken(value: string | null) {
  token = value
  if (value) localStorage.setItem(TOKEN_KEY, value)
  else localStorage.removeItem(TOKEN_KEY)
}

export function getToken() {
  return token
}

/** Error terstruktur dari API — membawa status HTTP dan error validasi Laravel. */
export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

type Method = 'GET' | 'POST' | 'PUT' | 'DELETE'

async function request<T>(method: Method, path: string, body?: unknown): Promise<T> {
  const headers: Record<string, string> = { Accept: 'application/json' }
  if (body !== undefined) headers['Content-Type'] = 'application/json'
  if (token) headers['Authorization'] = `Bearer ${token}`

  let res: Response
  try {
    res = await fetch(`${BASE}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  } catch {
    throw new ApiError(
      'Tidak bisa terhubung ke server. Pastikan backend (php artisan serve) sedang berjalan.',
      0,
    )
  }

  const isJson = res.headers.get('content-type')?.includes('application/json')
  const payload = isJson ? await res.json().catch(() => null) : null

  if (!res.ok) {
    const message =
      (payload && typeof payload === 'object' && 'message' in payload
        ? (payload as { message?: string }).message
        : null) ?? `Permintaan gagal (${res.status}).`
    const errors =
      payload && typeof payload === 'object' && 'errors' in payload
        ? (payload as { errors?: Record<string, string[]> }).errors
        : undefined
    throw new ApiError(message, res.status, errors)
  }

  return payload as T
}

/** Bentuk pembungkus `{ data: ... }` yang dipakai Laravel API Resource. */
export interface Wrapped<T> {
  data: T
}

export const api = {
  get: <T>(path: string) => request<T>('GET', path),
  post: <T>(path: string, body?: unknown) => request<T>('POST', path, body),
  put: <T>(path: string, body?: unknown) => request<T>('PUT', path, body),
  del: <T>(path: string) => request<T>('DELETE', path),
}
