<script setup lang="ts">
defineProps<{ title: string; subtitle: string }>()
import { useAuthStore } from '@/stores/auth'
import ProfilePhotoPicker from '@/components/account/ProfilePhotoPicker.vue'

const auth = useAuthStore()
</script>

<template>
  <div class="mb-[22px]">
    <h1 class="m-0 text-[29px] font-extrabold tracking-[-.02em]">{{ title }}</h1>
    <p class="mt-1.5 text-text-muted">{{ subtitle }}</p>
  </div>
  <div class="max-w-[560px] rounded-[18px] border border-border-card bg-white p-[26px]">
    <ProfilePhotoPicker class="mb-[22px]">
      <template #meta>
        <div class="text-lg font-extrabold">{{ auth.user?.name }}</div>
        <div class="text-[13px] font-bold text-gold">{{ auth.authRoleLabel }}</div>
      </template>
    </ProfilePhotoPicker>
    <label class="mb-1.5 block text-[13px] font-bold">Nama lengkap</label>
    <input v-model="auth.profileName" class="mb-3.5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5" />
    <label class="mb-1.5 block text-[13px] font-bold">Email</label>
    <input v-model="auth.profileEmail" class="mb-3.5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5" />
    <label class="mb-1.5 block text-[13px] font-bold">Nomor telepon</label>
    <input v-model="auth.profilePhone" class="mb-5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5" />
    <button
      type="button"
      class="rounded-[11px] bg-brand-blue px-7 py-3 font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-60"
      :disabled="auth.profileSaving"
      @click="auth.saveProfile"
    >
      {{ auth.profileSaving ? 'Menyimpan…' : 'Simpan perubahan' }}
    </button>
    <p v-if="auth.profileError" class="mt-3 text-[13px] leading-relaxed font-semibold text-danger" role="alert">
      {{ auth.profileError }}
    </p>
    <p v-else-if="auth.profileSaved" class="mt-3 text-[13px] font-semibold text-teal-deep" role="status">
        Profil berhasil disimpan.
    </p>
  </div>
</template>
