import { LOCATION_NAMES } from '@/data/categories'
import type { LocationName } from '@/types'

/**
 * Bounding box of Kota Balikpapan (all six kecamatan, small margin for the
 * coast and the Kariangau / Teritip edges). Mirrors `UmkmCatalog::BOUNDS`
 * in the backend, which enforces it again on save.
 */
export const BALIKPAPAN_BOUNDS = { latMin: -1.42, latMax: -0.95, lngMin: 116.68, lngMax: 117.15 } as const

/** City centre, for "no pin yet" previews. */
export const BALIKPAPAN_CENTER = { lat: -1.2654, lng: 116.8312 } as const

export interface LatLng {
  lat: number
  lng: number
}

/**
 * Any spelling of a kecamatan -> its canonical name, or null.
 * "barat", " Balikpapan  BARAT ", "Kec. Balikpapan Barat" -> "Balikpapan Barat".
 */
export function normalizeLocation(value: unknown): LocationName | null {
  if (typeof value !== 'string') return null
  const text = value
    .toLowerCase()
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/^(kecamatan|kec\.?)\s*/, '')
    .replace(/^(balikpapan|bpp|bppn)\s*/, '')
    .trim()
  return LOCATION_NAMES.find((name) => name.toLowerCase().slice('balikpapan '.length) === text) ?? null
}

export function isWithinBalikpapan({ lat, lng }: LatLng): boolean {
  const b = BALIKPAPAN_BOUNDS
  return lat >= b.latMin && lat <= b.latMax && lng >= b.lngMin && lng <= b.lngMax
}

function pair(lat: string, lng: string): LatLng | null {
  const point = { lat: Number(lat), lng: Number(lng) }
  return Number.isFinite(point.lat) && Number.isFinite(point.lng) ? point : null
}

const NUM = '(-?\\d{1,3}(?:\\.\\d+)?)'

export type ParseResult =
  | { ok: true; point: LatLng }
  | { ok: false; reason: 'empty' | 'short-link' | 'unrecognized' | 'outside'; point?: LatLng }

/**
 * Read a pin from what an owner typically pastes:
 *  - "-1.2654, 116.8312" (also with a space or semicolon between)
 *  - a full Google Maps link: ".../@-1.26,116.83,17z", "?q=-1.26,116.83",
 *    "?query=...", "ll=...", or the "!3d-1.26!4d116.83" place-link form
 *  - an OpenStreetMap link "#map=17/-1.26/116.83" or "?mlat=..&mlon=.."
 *
 * Short links (maps.app.goo.gl, goo.gl/maps) hide the coordinates behind a
 * redirect the browser cannot follow for us, so they are reported as such.
 * A point is also rejected when it falls outside Balikpapan - the usual
 * cause of a pin in the sea or another city is swapped lat/lng, a missing
 * minus sign, or a link to a different place.
 */
export function parseLocationInput(raw: string): ParseResult {
  const text = raw.trim()
  if (!text) return { ok: false, reason: 'empty' }

  if (/(maps\.app\.goo\.gl|goo\.gl\/maps|g\.co\/kgs)/i.test(text)) return { ok: false, reason: 'short-link' }

  let decoded = text
  try {
    decoded = decodeURIComponent(text)
  } catch {
    /* keep as-is */
  }

  const patterns: RegExp[] = [
    new RegExp(`!3d${NUM}!4d${NUM}`), // place pin - more exact than the viewport "@"
    new RegExp(`[?&](?:q|query|ll|destination|center)=(?:loc:)?${NUM}\\s*,\\s*${NUM}`),
    new RegExp(`[?&]mlat=${NUM}&mlon=${NUM}`),
    new RegExp(`#map=\\d+/${NUM}/${NUM}`),
    new RegExp(`@${NUM},${NUM}`),
    new RegExp(`^${NUM}\\s*[,;\\s]\\s*${NUM}$`),
  ]

  for (const re of patterns) {
    const m = decoded.match(re)
    if (!m) continue
    const point = pair(m[1]!, m[2]!)
    if (!point) continue
    if (isWithinBalikpapan(point)) return { ok: true, point }
    // Swapped order ("116.83, -1.26") is a common slip - accept it if that lands in the city.
    const swapped = { lat: point.lng, lng: point.lat }
    if (isWithinBalikpapan(swapped)) return { ok: true, point: swapped }
    return { ok: false, reason: 'outside', point }
  }

  return { ok: false, reason: 'unrecognized' }
}

export function parseErrorMessage(result: Extract<ParseResult, { ok: false }>): string {
  switch (result.reason) {
    case 'short-link':
      return 'Link pendek (maps.app.goo.gl) tidak memuat koordinat. Buka link itu, lalu salin URL lengkap dari address bar, atau tekan lama titik di Google Maps dan salin angka koordinatnya.'
    case 'outside':
      return `Titik ${result.point?.lat.toFixed(5)}, ${result.point?.lng.toFixed(5)} berada di luar Kota Balikpapan. Periksa tanda minus dan urutan latitude, longitude.`
    case 'unrecognized':
      return 'Format tidak dikenali. Tempel koordinat seperti "-1.2654, 116.8312" atau link Google Maps lengkap.'
    default:
      return ''
  }
}

export function formatLatLng(p: LatLng): string {
  return `${p.lat.toFixed(6)}, ${p.lng.toFixed(6)}`
}

/** Keyless OpenStreetMap embed centred on the pin, with a marker. */
export function osmEmbedUrl({ lat, lng }: LatLng, span = 0.006): string {
  const bbox = [lng - span, lat - span * 0.6, lng + span, lat + span * 0.6].map((n) => n.toFixed(6)).join(',')
  return `https://www.openstreetmap.org/export/embed.html?bbox=${bbox}&layer=mapnik&marker=${lat.toFixed(6)},${lng.toFixed(6)}`
}

/** Google Maps link that opens exactly on the pin (and offers directions). */
export function googleMapsPinUrl({ lat, lng }: LatLng): string {
  return `https://www.google.com/maps/search/?api=1&query=${lat.toFixed(6)},${lng.toFixed(6)}`
}

/**
 * Fallback when no pin is set: search the address *with* its kecamatan and
 * province, so a vague address like "RT 34" is not matched to a place with
 * the same name in another city.
 */
export function googleMapsSearchUrl(address: string, location: string): string {
  const parts = [address.trim(), location.trim() || 'Balikpapan', 'Kalimantan Timur']
  return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(parts.filter(Boolean).join(', '))}`
}
