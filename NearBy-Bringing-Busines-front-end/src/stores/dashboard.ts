import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { useUmkmStore } from './umkm'
import { useAccountStore } from './account'
import { CAT } from '@/data/categories'
import { MY_UMKM_RAW, SUBMISSIONS_RAW, USERS_RAW } from '@/data/dashboardSeed'
import type { TrashEntry, OwnerTrashEntry, UmkmStatus } from '@/types'

const STATUS_META: Record<UmkmStatus, { c: string; b: string }> = {
  Aktif: { c: '#2E7D6E', b: '#E3EFED' },
  Libur: { c: '#B07A1E', b: '#F7EDDC' },
  Tutup: { c: '#C0472F', b: '#F8E6E0' },
}

export const useDashboardStore = defineStore('dashboard', () => {
  const umkm = useUmkmStore()
  const account = useAccountStore()

  // ---- Owner: UMKM Saya / Ringkasan ----
  const deletedMyUmkm = ref<string[]>([])
  const ownerTrash = ref<OwnerTrashEntry[]>([])

  const myUmkm = computed(() =>
    MY_UMKM_RAW.filter((u) => !deletedMyUmkm.value.includes(u.name)).map((u) => {
      const status = umkm.statusOf(u.name)
      const sm = STATUS_META[status]
      return {
        ...u,
        status,
        statusColor: sm.c,
        statusBg: sm.b,
        catAccent: CAT[u.cat].accent,
        catSoft: CAT[u.cat].soft,
        statusOptions: (['Aktif', 'Libur', 'Tutup'] as UmkmStatus[]).map((opt) => ({
          label: opt,
          active: opt === status,
          bg: opt === status ? STATUS_META[opt].b : '#F4F0E7',
          color: opt === status ? STATUS_META[opt].c : '#8A8578',
          onClick: () => {
            umkm.umkmStatus[u.name] = opt
          },
        })),
      }
    }),
  )

  function ownerDeleteUmkm(name: string, cat: string, loc: string) {
    if (!confirm(`Pindahkan UMKM "${name}" ke Tempat Sampah?`)) return
    deletedMyUmkm.value.push(name)
    ownerTrash.value.unshift({ tid: `t${Date.now()}`, name, sub: `${cat} · ${loc}`, when: 'Baru saja' })
  }
  function ownerRestoreUmkm(tid: string) {
    const entry = ownerTrash.value.find((t) => t.tid === tid)
    if (!entry) return
    ownerTrash.value = ownerTrash.value.filter((t) => t.tid !== tid)
    deletedMyUmkm.value = deletedMyUmkm.value.filter((n) => n !== entry.name)
  }
  function ownerPurgeUmkm(tid: string, name: string) {
    if (!confirm(`Hapus permanen "${name}"? Data tidak bisa dipulihkan lagi.`)) return
    ownerTrash.value = ownerTrash.value.filter((t) => t.tid !== tid)
  }
  function emptyOwnerTrash() {
    if (!ownerTrash.value.length) return
    if (!confirm('Kosongkan Tempat Sampah? Semua item akan dihapus permanen dan tidak bisa dipulihkan.')) return
    ownerTrash.value = []
  }

  // ---- Admin: Semua UMKM ----
  const STATUS_LABEL_META: Record<string, { c: string; b: string }> = {
    Tampil: { c: '#2E7D6E', b: '#E3EFED' },
    Disembunyikan: { c: '#8A8578', b: '#EEEADF' },
    Ditinjau: { c: '#B07A1E', b: '#F7EDDC' },
  }
  const allUmkmAdmin = computed(() =>
    umkm.enrichedAll
      .filter((u) => !umkm.deletedUmkm.includes(u.id))
      .map((u) => {
        const hidden = umkm.hiddenUmkm.includes(u.id)
        const status = hidden ? 'Disembunyikan' : u.id === 8 ? 'Ditinjau' : 'Tampil'
        const meta = STATUS_LABEL_META[status]
        return { ...u, status, statusColor: meta.c, statusBg: meta.b, hidden }
      }),
  )

  function adminToggleHidden(id: number, name: string) {
    const hidden = umkm.hiddenUmkm.includes(id)
    if (hidden) umkm.hiddenUmkm = umkm.hiddenUmkm.filter((x) => x !== id)
    else umkm.hiddenUmkm.push(id)
    alert(`UMKM "${name}" ${hidden ? 'ditampilkan kembali.' : 'disembunyikan dari publik.'}`)
  }

  // ---- Admin: global Trash (UMKM + users) ----
  const trash = ref<TrashEntry[]>([])
  const purgedUsers = ref<string[]>([])

  const KIND_META = {
    user: { icon: '👤', iconBg: '#E6EDF8', iconColor: '#2C5EAD', kindLabel: 'Akun pengguna' },
    umkm: { icon: '🏪', iconBg: '#E3EFED', iconColor: '#3E8E82', kindLabel: 'UMKM' },
  } as const

  const trashList = computed(() => trash.value.map((t) => ({ ...t, ...KIND_META[t.kind] })))

  function adminDeleteUmkm(id: number, name: string, cat: string, loc: string) {
    if (!confirm(`Pindahkan UMKM "${name}" ke Tempat Sampah?`)) return
    umkm.deletedUmkm.push(id)
    trash.value.unshift({ tid: `t${Date.now()}`, kind: 'umkm', umkmId: id, name, sub: `${cat} · ${loc}`, when: 'Baru saja' })
  }
  function adminRestoreTrash(tid: string) {
    const entry = trash.value.find((t) => t.tid === tid)
    if (!entry) return
    trash.value = trash.value.filter((t) => t.tid !== tid)
    if (entry.kind === 'umkm' && entry.umkmId != null) {
      umkm.deletedUmkm = umkm.deletedUmkm.filter((id) => id !== entry.umkmId)
    }
  }
  function adminPurgeTrash(tid: string, name: string) {
    if (!confirm(`Hapus permanen "${name}"? Data tidak bisa dipulihkan lagi.`)) return
    const entry = trash.value.find((t) => t.tid === tid)
    trash.value = trash.value.filter((t) => t.tid !== tid)
    if (entry?.kind === 'user' && entry.email) purgedUsers.value.push(entry.email)
  }
  function emptyTrash() {
    if (!trash.value.length) return
    if (!confirm('Kosongkan Tempat Sampah? Semua item akan dihapus permanen dan tidak bisa dipulihkan.')) return
    purgedUsers.value.push(...trash.value.filter((t) => t.kind === 'user' && t.email).map((t) => t.email!))
    trash.value = []
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

  const users = computed(() => {
    const trashedEmails = trash.value
      .filter((t) => t.kind === 'user')
      .map((t) => t.email)
      .concat(purgedUsers.value)
    const active = USERS_RAW.filter((u) => !trashedEmails.includes(u.email)).map((u) => ({
      ...u,
      deleted: false,
      roleColor: ROLE_META[u.role].c,
      roleBg: ROLE_META[u.role].b,
      statusColor: USER_STATUS_META[u.status].c,
      statusBg: USER_STATUS_META[u.status].b,
    }))
    const deletedRows = account.deletedAccounts.map((a) => {
      const days = Math.max(0, 30 - Math.floor((Date.now() - a.deletedAt) / 86400000))
      const rm = ROLE_META[a.role as keyof typeof ROLE_META] ?? ROLE_META['Pengguna']
      return {
        name: a.name,
        email: a.email,
        role: a.role,
        joined: '',
        initial: a.initial,
        deleted: true,
        roleColor: rm.c,
        roleBg: rm.b,
        status: `Dihapus · ${days} hr lagi`,
        statusColor: '#C0472F',
        statusBg: '#FBEEEA',
      }
    })
    return [...active, ...deletedRows]
  })

  function userRestore(email: string) {
    account.deletedAccounts = account.deletedAccounts.filter((a) => a.email !== email)
    if (account.myDeleted?.email === email) account.myDeleted = null
  }
  function userReset(email: string) {
    alert(`Tautan reset password dikirim ke ${email}`)
  }
  function userDeactivate(name: string) {
    alert(`Akun "${name}" dinonaktifkan.`)
  }
  function userDelete(name: string, email: string, role: string) {
    trash.value.unshift({ tid: `t${Date.now()}`, kind: 'user', email, name, sub: `${email} · ${role}`, when: 'Baru saja' })
    alert(`Akun "${name}" dipindahkan ke Tempat Sampah.`)
  }

  // ---- Admin: Laporan ----
  const catBreakdown = computed(() => {
    const counts = umkm.categoryCards
    const max = Math.max(...counts.map((c) => c.count))
    return counts.map((c) => ({ ...c, pct: Math.round((c.count / max) * 100) }))
  })
  const topUmkm = computed(() =>
    [...umkm.enrichedAll]
      .sort((a, b) => b.rating - a.rating)
      .slice(0, 5)
      .map((u, i) => ({ ...u, rank: i + 1 })),
  )

  const pendingSubmissions = SUBMISSIONS_RAW.map((sub) => {
    const okCount = sub.checks.filter((c) => c[1]).length
    const total = sub.checks.length
    const complete = okCount === total
    const minor = okCount >= total - 2
    return {
      ...sub,
      checks: sub.checks.map(([label, ok]) => ({
        label,
        mark: ok ? '✓' : '✕',
        color: ok ? '#2E7D6E' : '#C0472F',
        bg: ok ? '#E3EFED' : '#F8E6E0',
      })),
      okCount,
      total,
      pct: Math.round((okCount / total) * 100),
      barColor: complete ? '#3E8E82' : minor ? '#C98A2E' : '#C0472F',
      verdict: complete ? 'Data lengkap' : minor ? 'Kurang lengkap' : 'Data belum memadai',
      verdictColor: complete ? '#2E7D6E' : minor ? '#B07A1E' : '#C0472F',
      verdictBg: complete ? '#E3EFED' : minor ? '#F7EDDC' : '#F8E6E0',
      catAccent: CAT[sub.cat].accent,
      catSoft: CAT[sub.cat].soft,
    }
  })

  function approveSubmission(name: string) {
    alert(`UMKM "${name}" disetujui dan akan ditampilkan di website.`)
  }
  function rejectSubmission(name: string) {
    alert(`Pengajuan "${name}" ditolak.`)
  }
  function requestFix(name: string) {
    alert(`Permintaan perbaikan data dikirim ke pemilik "${name}".`)
  }

  return {
    myUmkm,
    ownerTrash,
    ownerDeleteUmkm,
    ownerRestoreUmkm,
    ownerPurgeUmkm,
    emptyOwnerTrash,
    allUmkmAdmin,
    adminToggleHidden,
    adminDeleteUmkm,
    trash,
    trashList,
    adminRestoreTrash,
    adminPurgeTrash,
    emptyTrash,
    users,
    userRestore,
    userReset,
    userDeactivate,
    userDelete,
    catBreakdown,
    topUmkm,
    pendingSubmissions,
    approveSubmission,
    rejectSubmission,
    requestFix,
  }
})
