import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch } from '@/lib/api'
import { enrichUmkm, umkmFromApi, type EnrichedUmkm } from './umkm'
import { CAT } from '@/data/categories'
import type {
  OwnerTrashEntry,
  ProblemReport,
  ProblemReportStatus,
  Question,
  QuestionStatus,
  Review,
  StatCard,
  SubmissionFile,
  UmkmStatus,
} from '@/types'

const STATUS_META: Record<UmkmStatus, { c: string; b: string }> = {
  Aktif: { c: '#2E7D6E', b: '#E3EFED' },
  Libur: { c: '#B07A1E', b: '#F7EDDC' },
  Tutup: { c: '#C0472F', b: '#F8E6E0' },
}

function fromApiReview(row: any): Review {
  return {
    id: String(row.id),
    umkmId: row.umkmId,
    umkmName: row.umkmName,
    userId: row.userId ?? null,
    initial: row.initial,
    name: row.name,
    stars: Number(row.stars) || 0,
    date: row.date ?? '',
    text: row.text ?? '',
    reply: row.reply ?? null,
  }
}

export const useDashboardStore = defineStore('dashboard', () => {
  // ---- Owner: Ringkasan / UMKM Saya ----
  const ownerUmkmRaw = ref<EnrichedUmkm[]>([])
  const ownerSummary = ref<{ views: number; rating: number; reviews: number; favorites: number; umkmCount: number } | null>(null)
  const ownerReviewsRaw = ref<Review[]>([])
  const ownerTrashRaw = ref<EnrichedUmkm[]>([])
  const ownerLoading = ref(false)

  async function fetchOwnerDashboard() {
    ownerLoading.value = true
    try {
      const [summaryRes, reviewsRes, trashRes] = await Promise.all([
        apiFetch<{ stats: typeof ownerSummary.value; umkms: any[] }>('/owner/summary'),
        apiFetch<any[]>('/owner/reviews'),
        apiFetch<any[]>('/owner/trash'),
      ])
      ownerSummary.value = summaryRes.stats
      ownerUmkmRaw.value = summaryRes.umkms.map((row) => enrichUmkm(umkmFromApi(row)))
      ownerReviewsRaw.value = reviewsRes.map(fromApiReview)
      ownerTrashRaw.value = trashRes.map((row) => enrichUmkm(umkmFromApi(row)))
    } finally {
      ownerLoading.value = false
    }
  }

  const myUmkm = computed(() =>
    ownerUmkmRaw.value.map((u) => {
      const sm = STATUS_META[u.status]
      return {
        ...u,
        statusColor: sm.c,
        statusBg: sm.b,
        catAccent: u.accent,
        catSoft: u.soft,
        views: u.views,
        statusOptions: (['Aktif', 'Libur', 'Tutup'] as UmkmStatus[]).map((opt) => ({
          label: opt,
          active: opt === u.status,
          bg: opt === u.status ? STATUS_META[opt].b : '#F4F0E7',
          color: opt === u.status ? STATUS_META[opt].c : '#8A8578',
          onClick: async () => {
            const idx = ownerUmkmRaw.value.findIndex((x) => x.id === u.id)
            const STATUS_TO_API: Record<UmkmStatus, string> = { Aktif: 'aktif', Libur: 'libur', Tutup: 'tutup' }
            const row = await apiFetch<any>(`/umkm/${u.id}`, {
              method: 'PUT',
              body: JSON.stringify({ status: STATUS_TO_API[opt] }),
            })
            if (idx !== -1) ownerUmkmRaw.value[idx] = enrichUmkm(umkmFromApi(row))
          },
        })),
      }
    }),
  )

  const ownerTrash = computed<OwnerTrashEntry[]>(() =>
    ownerTrashRaw.value.map((u) => ({
      tid: String(u.id),
      name: u.name,
      sub: `${u.cat} · ${u.loc}`,
      when: u.deletedAt ?? '',
    })),
  )

  async function ownerDeleteUmkm(id: number, name: string) {
    if (!confirm(`Pindahkan UMKM "${name}" ke Tempat Sampah?`)) return
    await apiFetch(`/umkm/${id}`, { method: 'DELETE' })
    const row = ownerUmkmRaw.value.find((u) => u.id === id)
    ownerUmkmRaw.value = ownerUmkmRaw.value.filter((u) => u.id !== id)
    if (row) ownerTrashRaw.value = [{ ...row, deletedAt: 'Baru saja' }, ...ownerTrashRaw.value]
  }
  async function ownerRestoreUmkm(tid: string) {
    const row = await apiFetch<any>(`/owner/umkm/${tid}/restore`, { method: 'POST' })
    ownerTrashRaw.value = ownerTrashRaw.value.filter((u) => String(u.id) !== tid)
    ownerUmkmRaw.value = [...ownerUmkmRaw.value, enrichUmkm(umkmFromApi(row))]
  }
  async function ownerPurgeUmkm(tid: string, name: string) {
    if (!confirm(`Hapus permanen "${name}"? Data tidak bisa dipulihkan lagi.`)) return
    await apiFetch(`/owner/umkm/${tid}/force`, { method: 'DELETE' })
    ownerTrashRaw.value = ownerTrashRaw.value.filter((u) => String(u.id) !== tid)
  }
  async function emptyOwnerTrash() {
    if (!ownerTrashRaw.value.length) return
    if (!confirm('Kosongkan Tempat Sampah? Semua item akan dihapus permanen dan tidak bisa dipulihkan.')) return
    await apiFetch('/owner/trash', { method: 'DELETE' })
    ownerTrashRaw.value = []
  }

  const ownerStats = computed<StatCard[]>(() => {
    const s = ownerSummary.value
    return [
      { icon: 'eye', value: (s?.views ?? 0).toLocaleString('id-ID'), label: 'Kunjungan profil', accent: '#2C5EAD', soft: '#E6EDF8' },
      { icon: 'star', value: String(s?.rating ?? 0), label: 'Rating rata-rata', accent: '#C98A2E', soft: '#F7EDDC' },
      { icon: 'edit', value: String(s?.reviews ?? 0), label: 'Total ulasan', accent: '#3E8E82', soft: '#E3EFED' },
      { icon: 'heart', value: String(s?.favorites ?? 0), label: 'Disimpan favorit', accent: '#1591DC', soft: '#E1F1FB' },
    ]
  })

  /** Views per owned UMKM — a real substitute for the old fake "7-day visits" chart. */
  const ownerViewsBars = computed(() => {
    const max = Math.max(1, ...ownerUmkmRaw.value.map((u) => u.views))
    return ownerUmkmRaw.value.map((u) => ({ label: u.name, pct: Math.round((u.views / max) * 100), value: u.views }))
  })

  const ownerRecentReviews = computed(() => ownerReviewsRaw.value.slice(0, 3))

  // ---- Owner: Ulasan (full list + reply) ----
  async function replyToReview(reviewId: string, reply: string) {
    const row = await apiFetch<any>(`/reviews/${reviewId}/reply`, { method: 'POST', body: JSON.stringify({ reply }) })
    const updated = fromApiReview(row)
    const idx = ownerReviewsRaw.value.findIndex((r) => r.id === reviewId)
    if (idx !== -1) ownerReviewsRaw.value[idx] = updated
  }

  // ---- Admin: Semua UMKM ----
  const adminUmkmRaw = ref<EnrichedUmkm[]>([])
  const adminLoading = ref(false)

  async function fetchAdminUmkm() {
    const rows = await apiFetch<any[]>('/admin/umkm')
    adminUmkmRaw.value = rows.map((row) => enrichUmkm(umkmFromApi(row)))
  }

  const allUmkmAdmin = computed(() =>
    adminUmkmRaw.value.map((u) => {
      const label = u.hidden ? 'Disembunyikan' : u.verification === 'menunggu' ? 'Ditinjau' : u.verification === 'ditolak' ? 'Ditolak' : 'Tampil'
      const meta =
        label === 'Tampil'
          ? { c: '#2E7D6E', b: '#E3EFED' }
          : label === 'Disembunyikan'
            ? { c: '#8A8578', b: '#EEEADF' }
            : label === 'Ditolak'
              ? { c: '#C0472F', b: '#F8E6E0' }
              : { c: '#B07A1E', b: '#F7EDDC' }
      return { ...u, status: label, statusColor: meta.c, statusBg: meta.b }
    }),
  )

  async function adminToggleHidden(id: number, name: string) {
    const row = await apiFetch<any>(`/admin/umkm/${id}/toggle-hidden`, { method: 'POST' })
    const updated = enrichUmkm(umkmFromApi(row))
    const idx = adminUmkmRaw.value.findIndex((u) => u.id === id)
    if (idx !== -1) adminUmkmRaw.value[idx] = updated
    alert(`UMKM "${name}" ${updated.hidden ? 'dinonaktifkan.' : 'diaktifkan kembali.'}`)
  }

  // ---- Admin: Pengguna ----
  const ROLE_META = {
    Pengguna: { c: '#1591DC', b: '#E1F1FB' },
    'Pemilik UMKM': { c: '#3E8E82', b: '#E3EFED' },
    Administrator: { c: '#C98A2E', b: '#F7EDDC' },
  } as const
  const USER_STATUS_META = {
    Aktif: { c: '#2E7D6E', b: '#E3EFED' },
    Menunggu: { c: '#B07A1E', b: '#F7EDDC' },
    Nonaktif: { c: '#8A8578', b: '#EEEADF' },
  } as const
  const STATUS_LABEL: Record<string, keyof typeof USER_STATUS_META> = { aktif: 'Aktif', menunggu: 'Menunggu', nonaktif: 'Nonaktif' }

  interface AdminUserRow {
    id: number
    name: string
    email: string
    role: keyof typeof ROLE_META
    status: keyof typeof USER_STATUS_META
    joined: string
    initial: string
    deletedAt: string | null
  }

  const adminUsersRaw = ref<AdminUserRow[]>([])
  const adminTrashUsersRaw = ref<AdminUserRow[]>([])

  function fromApiUser(row: any): AdminUserRow {
    return {
      id: row.id,
      name: row.name,
      email: row.email,
      role: row.roleLabel,
      status: STATUS_LABEL[row.status] ?? 'Aktif',
      joined: row.joined ?? '',
      initial: row.initial,
      deletedAt: row.deletedAt ?? null,
    }
  }

  async function fetchAdminUsers() {
    const [activeRes, trashRes] = await Promise.all([
      apiFetch<any[]>('/admin/users'),
      apiFetch<{ users: any[] }>('/admin/trash'),
    ])
    adminUsersRaw.value = activeRes.map(fromApiUser)
    adminTrashUsersRaw.value = trashRes.users.map(fromApiUser)
  }

  const users = computed(() => {
    const active = adminUsersRaw.value.map((u) => ({
      ...u,
      deleted: false,
      roleColor: ROLE_META[u.role].c,
      roleBg: ROLE_META[u.role].b,
      statusColor: USER_STATUS_META[u.status].c,
      statusBg: USER_STATUS_META[u.status].b,
    }))
    const deletedRows = adminTrashUsersRaw.value.map((u) => {
      const days = u.deletedAt ? Math.max(0, 30 - Math.floor((Date.now() - new Date(u.deletedAt).getTime()) / 86400000)) : 30
      const rm = ROLE_META[u.role] ?? ROLE_META.Pengguna
      return {
        ...u,
        deleted: true,
        roleColor: rm.c,
        roleBg: rm.b,
        status: `Dihapus · ${days} hr lagi` as unknown as AdminUserRow['status'],
        statusColor: '#C0472F',
        statusBg: '#FBEEEA',
      }
    })
    return [...active, ...deletedRows]
  })

  async function userRestore(id: number) {
    await apiFetch(`/admin/trash/${id}/restore?type=user`, { method: 'POST' })
    adminTrashUsersRaw.value = adminTrashUsersRaw.value.filter((u) => u.id !== id)
    await fetchAdminUsers()
  }
  function userReset(email: string) {
    // No email/reset-token flow is wired up yet — this is an honest placeholder, not a real send.
    alert(`Belum ada layanan email terhubung — tautan reset untuk ${email} belum benar-benar terkirim.`)
  }
  async function userToggleActive(id: number, name: string) {
    const row = await apiFetch<any>(`/admin/users/${id}/toggle-status`, { method: 'POST' })
    const updated = fromApiUser(row)
    const idx = adminUsersRaw.value.findIndex((u) => u.id === id)
    if (idx !== -1) adminUsersRaw.value[idx] = updated
    alert(`Akun "${name}" ${updated.status === 'Nonaktif' ? 'dinonaktifkan.' : 'diaktifkan kembali.'}`)
  }

  // ---- Admin: Laporan ----
  const adminReports = ref<{
    stats: { umkmCount: number; userCount: number; reviewCount: number; avgRating: number }
    byCategory: Record<string, number>
    byLocation: Record<string, number>
    growth: { label: string; val: number }[]
  } | null>(null)

  async function fetchAdminReports() {
    adminReports.value = await apiFetch('/admin/reports')
  }

  const reportStats = computed<StatCard[]>(() => {
    const s = adminReports.value?.stats
    return [
      { icon: 'grid', value: String(s?.umkmCount ?? 0), label: 'UMKM terdaftar', accent: '#2C5EAD', soft: '#E6EDF8' },
      { icon: 'target', value: String(s?.userCount ?? 0), label: 'Total pengguna', accent: '#1591DC', soft: '#E1F1FB' },
      { icon: 'edit', value: String(s?.reviewCount ?? 0), label: 'Total ulasan', accent: '#3E8E82', soft: '#E3EFED' },
      { icon: 'star', value: String(s?.avgRating ?? 0), label: 'Rata-rata rating', accent: '#C98A2E', soft: '#F7EDDC' },
    ]
  })

  const growthBars = computed(() => {
    const bars = adminReports.value?.growth ?? []
    const max = Math.max(1, ...bars.map((b) => b.val))
    return bars.map((b) => ({ ...b, pct: Math.round((b.val / max) * 100) }))
  })

  const catBreakdown = computed(() => {
    const byCategory = adminReports.value?.byCategory ?? {}
    const counts = Object.keys(CAT).map((name) => ({ name, ...CAT[name as keyof typeof CAT], count: byCategory[name] ?? 0 }))
    const max = Math.max(1, ...counts.map((c) => c.count))
    return counts.map((c) => ({ ...c, pct: Math.round((c.count / max) * 100) }))
  })

  const topUmkm = computed(() =>
    [...adminUmkmRaw.value]
      .sort((a, b) => b.rating - a.rating)
      .slice(0, 5)
      .map((u, i) => ({ ...u, rank: i + 1 })),
  )

  // ---- Admin: Verifikasi ----
  interface PendingSubmission {
    id: number
    name: string
    owner: string
    cat: string
    loc: string
    date: string
    checks: [string, boolean][]
    files: SubmissionFile[]
  }
  const pendingSubmissionsRaw = ref<PendingSubmission[]>([])

  async function fetchSubmissions() {
    pendingSubmissionsRaw.value = await apiFetch<PendingSubmission[]>('/admin/submissions')
  }

  const pendingSubmissions = computed(() =>
    pendingSubmissionsRaw.value.map((sub) => {
      const okCount = sub.checks.filter((c) => c[1]).length
      const total = sub.checks.length
      const complete = okCount === total
      const minor = okCount >= total - 2
      return {
        ...sub,
        checks: sub.checks.map(([label, ok]) => ({
          label,
          mark: ok,
          color: ok ? '#2E7D6E' : '#C0472F',
          bg: ok ? '#E3EFED' : '#F8E6E0',
        })),
        okCount,
        total,
        pct: total ? Math.round((okCount / total) * 100) : 0,
        barColor: complete ? '#3E8E82' : minor ? '#C98A2E' : '#C0472F',
        verdict: complete ? 'Data lengkap' : minor ? 'Kurang lengkap' : 'Data belum memadai',
        verdictColor: complete ? '#2E7D6E' : minor ? '#B07A1E' : '#C0472F',
        verdictBg: complete ? '#E3EFED' : minor ? '#F7EDDC' : '#F8E6E0',
        catAccent: CAT[sub.cat as keyof typeof CAT]?.accent ?? '#5B6672',
        catSoft: CAT[sub.cat as keyof typeof CAT]?.soft ?? '#EEF0F2',
      }
    }),
  )

  async function approveSubmission(id: number, name: string) {
    await apiFetch(`/admin/submissions/${id}/approve`, { method: 'POST' })
    pendingSubmissionsRaw.value = pendingSubmissionsRaw.value.filter((s) => s.id !== id)
    alert(`UMKM "${name}" disetujui dan akan ditampilkan di website.`)
  }
  async function rejectSubmission(id: number, name: string) {
    await apiFetch(`/admin/submissions/${id}/reject`, { method: 'POST' })
    pendingSubmissionsRaw.value = pendingSubmissionsRaw.value.filter((s) => s.id !== id)
    alert(`Pengajuan "${name}" ditolak.`)
  }
  function requestFix(name: string) {
    // There's no owner-facing resubmission/notification flow yet — this stays a local nudge for now.
    alert(`Permintaan perbaikan data dikirim ke pemilik "${name}".`)
  }

  // ---- Admin: Laporan Masalah ----
  const PROBLEM_STATUS_META: Record<ProblemReportStatus, { c: string; b: string }> = {
    Baru: { c: '#B07A1E', b: '#F7EDDC' },
    Ditinjau: { c: '#2C5EAD', b: '#E6EDF8' },
    Selesai: { c: '#2E7D6E', b: '#E3EFED' },
  }
  const problemReportsRaw = ref<ProblemReport[]>([])

  async function fetchProblemReports() {
    problemReportsRaw.value = await apiFetch<ProblemReport[]>('/admin/problem-reports')
  }

  const problemReports = computed(() =>
    [...problemReportsRaw.value]
      .sort((a, b) => (a.status === b.status ? 0 : a.status === 'Baru' ? -1 : b.status === 'Baru' ? 1 : 0))
      .map((r) => {
        const meta = PROBLEM_STATUS_META[r.status]
        return { ...r, statusColor: meta.c, statusBg: meta.b }
      }),
  )
  const newReportCount = computed(() => problemReportsRaw.value.filter((r) => r.status === 'Baru').length)

  /** Submit a bug/issue report from the help widget (works for guests too). */
  async function submitProblemReport(kind: string, text: string, name: string): Promise<boolean> {
    try {
      await apiFetch('/problem-reports', { method: 'POST', body: JSON.stringify({ kind, text, name: name.trim() || undefined }) })
      return true
    } catch (e) {
      return false
    }
  }
  async function setProblemReportStatus(id: string, status: ProblemReportStatus) {
    const row = await apiFetch<ProblemReport>(`/admin/problem-reports/${id}/status`, {
      method: 'POST',
      body: JSON.stringify({ status }),
    })
    const idx = problemReportsRaw.value.findIndex((r) => r.id === id)
    if (idx !== -1) problemReportsRaw.value[idx] = row
  }

  // ---- Admin: Pertanyaan (help widget "Bertanya") ----
  const QUESTION_STATUS_META: Record<QuestionStatus, { c: string; b: string }> = {
    Baru: { c: '#B07A1E', b: '#F7EDDC' },
    Dijawab: { c: '#2E7D6E', b: '#E3EFED' },
    Ditutup: { c: '#8A8578', b: '#EEEADF' },
  }
  const questionsRaw = ref<Question[]>([])

  async function fetchQuestions() {
    questionsRaw.value = await apiFetch<Question[]>('/admin/questions')
  }

  const questions = computed(() =>
    questionsRaw.value.map((q) => {
      const meta = QUESTION_STATUS_META[q.status]
      return { ...q, statusColor: meta.c, statusBg: meta.b }
    }),
  )
  const newQuestionCount = computed(() => questionsRaw.value.filter((q) => q.status === 'Baru').length)

  /** Send a question from the help widget (works for guests too). */
  async function submitQuestion(text: string, name: string, contact: string): Promise<boolean> {
    try {
      await apiFetch('/questions', {
        method: 'POST',
        body: JSON.stringify({ text, name: name.trim() || undefined, contact: contact.trim() || undefined }),
      })
      return true
    } catch {
      return false
    }
  }

  async function answerQuestion(id: string, answer: string) {
    const row = await apiFetch<Question>(`/admin/questions/${id}/answer`, {
      method: 'POST',
      body: JSON.stringify({ answer }),
    })
    const idx = questionsRaw.value.findIndex((q) => q.id === id)
    if (idx !== -1) questionsRaw.value[idx] = row
  }

  async function setQuestionStatus(id: string, status: QuestionStatus) {
    const row = await apiFetch<Question>(`/admin/questions/${id}/status`, {
      method: 'POST',
      body: JSON.stringify({ status }),
    })
    const idx = questionsRaw.value.findIndex((q) => q.id === id)
    if (idx !== -1) questionsRaw.value[idx] = row
  }

  /** Called once from DashboardView when an admin opens the panel. */
  async function fetchAdminDashboard() {
    adminLoading.value = true
    try {
      await Promise.all([
        fetchAdminUmkm(),
        fetchAdminUsers(),
        fetchAdminReports(),
        fetchSubmissions(),
        fetchProblemReports(),
        fetchQuestions(),
      ])
    } finally {
      adminLoading.value = false
    }
  }

  return {
    // Owner
    ownerLoading,
    fetchOwnerDashboard,
    myUmkm,
    ownerStats,
    ownerViewsBars,
    ownerRecentReviews,
    ownerReviewsRaw,
    replyToReview,
    ownerTrash,
    ownerDeleteUmkm,
    ownerRestoreUmkm,
    ownerPurgeUmkm,
    emptyOwnerTrash,
    // Admin
    adminLoading,
    fetchAdminDashboard,
    allUmkmAdmin,
    adminToggleHidden,
    users,
    userRestore,
    userReset,
    userToggleActive,
    reportStats,
    growthBars,
    catBreakdown,
    topUmkm,
    problemReports,
    newReportCount,
    submitProblemReport,
    setProblemReportStatus,
    questions,
    newQuestionCount,
    submitQuestion,
    answerQuestion,
    setQuestionStatus,
    pendingSubmissions,
    approveSubmission,
    rejectSubmission,
    requestFix,
  }
})
