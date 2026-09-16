<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'
import ProfilePhotoPicker from '@/components/account/ProfilePhotoPicker.vue'

const auth = useAuthStore()
</script>

<template>
  <div class="rounded-2xl border border-border-card bg-white p-[22px]">
    <ProfilePhotoPicker class="mb-5" size="sm" />
    <label class="mb-1.5 block text-[13px] font-bold text-brand-navy">Nama lengkap</label>
    <input v-model="auth.profileName" class="mb-3.5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy" />
    <label class="mb-1.5 block text-[13px] font-bold text-brand-navy">Email</label>
    <input v-model="auth.profileEmail" class="mb-3.5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy" />
    <label class="mb-1.5 block text-[13px] font-bold text-brand-navy">Nomor telepon</label>
    <input v-model="auth.profilePhone" class="mb-5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy" />
    <button
      type="button"
      class="rounded-[11px] bg-brand-blue px-[26px] py-3 font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-60"
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
