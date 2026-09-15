{
  path: '/dashboard/umkm',
  name: 'umkm-dashboard',
  component: () => import('@/views/dashboard/UmkmDashboard.vue'),
  meta: { requiresAuth: true, role: 'umkm_owner' },
}
