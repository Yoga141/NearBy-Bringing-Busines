export type CategoryName = 'Kuliner' | 'Penginapan' | 'Fashion' | 'Oleh-Oleh' | 'Jasa'

export type LocationName =
  | 'Balikpapan Kota'
  | 'Balikpapan Utara'
  | 'Balikpapan Selatan'
  | 'Balikpapan Timur'
  | 'Balikpapan Barat'
  | 'Balikpapan Tengah'

export interface CategoryStyle {
  accent: string
  soft: string
  initial: string
  icon: string
}

export interface UmkmItem {
  name: string
  price: string
  img?: string
  avail?: boolean
}

export type UmkmVerification = 'menunggu' | 'disetujui' | 'ditolak'

export interface Umkm {
  id: number
  ownerId: number | null
  name: string
  cat: CategoryName
  loc: LocationName
  rating: number
  reviews: number
  priceLabel: string
  tag: string
  imgLabel: string
  address: string
  hours: string
  phone: string
  ig: string
  listLabel: string
  items: UmkmItem[]
  status: UmkmStatus
  verification: UmkmVerification
  hidden: boolean
  views: number
  /** Only present once the UMKM has been soft-deleted (owner/admin trash views). */
  deletedAt?: string | null
}

export type UmkmStatus = 'Aktif' | 'Libur' | 'Tutup'

export interface Review {
  id: string
  umkmId: number
  /** Present when the review was fetched alongside its UMKM (e.g. the owner's or the user's own review list). */
  umkmName?: string
  umkmCat?: CategoryName
  userId: number | null
  initial: string
  name: string
  stars: number
  date: string
  text: string
  reply?: string | null
}

export type Role = 'user' | 'owner' | 'admin'

export interface AuthUser {
  id: number
  name: string
  email: string
  phone: string | null
  role: Role
  status: string
  /** Uploaded profile photo, or null to fall back to the initial avatar. */
  avatarUrl: string | null
}

// ---- Dashboard ----

export interface StatCard {
  icon: string
  value: string
  label: string
  delta?: string
  accent: string
  soft: string
}

export interface SubmissionFile {
  name: string
  kind: 'image' | 'doc'
  ok: boolean
  meta: string
}

export interface OwnerTrashEntry {
  tid: string
  name: string
  sub: string
  when: string
}

export type ProblemReportStatus = 'Baru' | 'Ditinjau' | 'Selesai'

export interface ProblemReport {
  id: string
  kind: string
  text: string
  name: string
  status: ProblemReportStatus
  when: string
}

export type QuestionStatus = 'Baru' | 'Dijawab' | 'Ditutup'

/** A question sent through "Pusat Bantuan → Bertanya". */
export interface Question {
  id: string
  text: string
  name: string
  /** How the admin can reach the asker; there is no in-app reply channel. */
  contact: string | null
  answer: string | null
  status: QuestionStatus
  when: string
}

// ---- Konten medsos ----

/** A registration tutorial video an admin uploaded, hosted by us and played on /panduan. */
export interface GuideVideo {
  id: number
  title: string
  description: string | null
  /** Relative streaming URL (`/api/guide-video/{id}/file`) — same origin as the SPA. */
  url: string
  mimeType: string
  size: number
  /** Ready-to-print size, e.g. "12,4 MB". */
  sizeLabel: string
  originalName: string
  active: boolean
  /** True when the row survived but its file did not — admin list only. */
  fileMissing: boolean
  /** Admin list only; absent from the public endpoint. */
  uploadedBy?: string | null
  uploadedAt: string | null
}

/** Upload ceiling reported by the API, so the form can state it before uploading. */
export interface GuideVideoLimits {
  maxBytes: number
  configuredBytes: number
  phpLimitBytes: number
  /** True when php.ini is stricter than the app's own limit — worth warning about. */
  phpLimited: boolean
  allowedMimes: string[]
}

/** One signed-in device, backed by a Sanctum personal access token. */
export interface AccountSession {
  id: number
  /** e.g. "Chrome · Windows"; tokens carry no IP or location, so none is shown. */
  device: string
  /** The device making the current request — it can't sign itself out here. */
  current: boolean
  mobile: boolean
  lastUsed: string
  createdAt: string | null
}

export type VideoPlatform = 'youtube' | 'instagram'

/** One card in the homepage "Video dari medsos NearBy" section. */
export interface SocialVideo {
  id: number
  platform: VideoPlatform
  platformLabel: string
  title: string
  /** Share link an admin pasted; null while the slot is still empty. */
  url: string | null
  /** Player URL derived from `url` by the API; null when the slot is empty. */
  embedUrl: string | null
  /** Poster image — YouTube only; Instagram exposes no public thumbnail. */
  thumbnailUrl: string | null
  sortOrder: number
  active: boolean
}
