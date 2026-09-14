<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useDashboardStore } from '@/stores/dashboard'
import DashboardLayout from '@/components/dashboard/DashboardLayout.vue'
import DashboardModals from '@/components/dashboard/DashboardModals.vue'
import VerifikasiTab from '@/components/dashboard/admin/VerifikasiTab.vue'
import SemuaUmkmTab from '@/components/dashboard/admin/SemuaUmkmTab.vue'
import PenggunaTab from '@/components/dashboard/admin/PenggunaTab.vue'
import LaporanTab from '@/components/dashboard/admin/LaporanTab.vue'
import LaporanMasalahTab from '@/components/dashboard/admin/LaporanMasalahTab.vue'
import VideoMedsosTab from '@/components/dashboard/admin/VideoMedsosTab.vue'
import RingkasanTab from '@/components/dashboard/owner/RingkasanTab.vue'
import MyUmkmTab from '@/components/dashboard/owner/MyUmkmTab.vue'
import UlasanTab from '@/components/dashboard/owner/UlasanTab.vue'
import TrashTab from '@/components/dashboard/shared/TrashTab.vue'
import ProfileEditCard from '@/components/dashboard/shared/ProfileEditCard.vue'

const props = defineProps<{ tab?: string }>()

const auth = useAuthStore()
const dashboard = useDashboardStore()
const isAdmin = computed(() => auth.user?.role === 'admin')
const tab = computed(() => props.tab ?? (isAdmin.value ? 'verif' : 'ringkasan'))

// Load once up front so the sidebar's badge counts (trash / new reports) and
// every tab have data ready regardless of which one is opened first.
onMounted(() => {
  if (isAdmin.value) dashboard.fetchAdminDashboard()
  else dashboard.fetchOwnerDashboard()
})
</script>

<template>
  <DashboardLayout :tab="tab">
    <template v-if="isAdmin">
      <VerifikasiTab v-if="tab === 'verif'" />
      <SemuaUmkmTab v-else-if="tab === 'all'" />
      <PenggunaTab v-else-if="tab === 'users'" />
      <LaporanTab v-else-if="tab === 'report'" />
      <LaporanMasalahTab v-else-if="tab === 'masalah'" />
      <VideoMedsosTab v-else-if="tab === 'video'" />
      <ProfileEditCard v-else-if="tab === 'profil'" title="Edit Profil" subtitle="Perbarui informasi akun administrasimu." />
    </template>
    <template v-else>
      <RingkasanTab v-if="tab === 'ringkasan'" />
      <MyUmkmTab v-else-if="tab === 'myumkm'" />
      <UlasanTab v-else-if="tab === 'ulasan'" />
      <TrashTab v-else-if="tab === 'trash'" />
      <ProfileEditCard v-else-if="tab === 'profil'" title="Profil" subtitle="Perbarui informasi akun pemilik UMKM-mu." />
    </template>
  </DashboardLayout>

  <DashboardModals />
</template>
