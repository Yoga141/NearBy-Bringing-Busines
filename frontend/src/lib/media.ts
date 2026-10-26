/**
 * Links to uploaded photos (UMKM gallery/cover, product, avatar).
 *
 * In production the SPA and the Laravel backend live on different domains
 * (www.nearbybalikpapan.com vs api.nearbybalikpapan.com). The API now sends
 * absolute links, but a relative "/storage/..." (older data, or an older API)
 * would load from the SPA's own domain and get index.html instead of the
 * image. resolveMediaUrl() points such paths at the backend.
 *
 * VITE_API_URL is the backend's origin, e.g. https://api.nearbybalikpapan.com
 * (see .env.production). Unset - as in `npm run dev`, where Vite proxies
 * /storage and /api - leaves paths relative.
 */
export const API_ORIGIN: string = (import.meta.env.VITE_API_URL ?? '').trim().replace(/\/+$/, '')

/** "/storage/a.jpg" or "storage/a.jpg" => "https://api.../storage/a.jpg"; absolute links, icon keys, blob/data URLs unchanged. */
export function resolveMediaUrl(url: string): string
export function resolveMediaUrl(url: string | null | undefined): string | null | undefined
export function resolveMediaUrl(url: string | null | undefined): string | null | undefined {
  if (!url || !API_ORIGIN) return url
  const trimmed = url.trim()
  if (!/^\/?(storage|api)\//i.test(trimmed)) return url
  return `${API_ORIGIN}/${trimmed.replace(/^\/+/, '')}`
}
