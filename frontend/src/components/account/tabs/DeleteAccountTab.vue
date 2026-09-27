<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAccountStore } from '@/stores/account'
import { useUiStore } from '@/stores/ui'
import ClockIcon from '@/components/shared/ClockIcon.vue'

const auth = useAuthStore()
const account = useAccountStore()
const ui = useUiStore()
const router = useRouter()

const delConfirm = ref('')
const password = ref('')
const canDelete = computed(() => delConfirm.value.trim().toUpperCase() === 'HAPUS' && password.value.length > 0)

async function confirmDelete() {
  if (!canDelete.value || !auth.user || account.deleting) return
  const ok = await account.deleteMyAccount(password.value)
  if (!ok) return
  password.value = ''
  delConfirm.value = ''
  // The server already revoked every token; clear the local session too.
  auth.logout()
  ui.closeSettings()
  router.push({ name: 'beranda' })
  alert('Akunmu sudah dihapus.')
}
</script>

<template>
  <div class="rounded-2xl border border-danger-border bg-danger-tint-2 p-[22px]">
    <div class="mb-2 text-[15px] font-extrabold text-[#8A2818]">Hapus akun ini</div>
    <p v-if="auth.isAdmin" class="text-[13.5px] leading-relaxed text-[#7A4A40]">
      Akun administrator tidak bisa dihapus dari halaman akun.
    </p>
    <template v-else>
      <p class="mb-3.5 text-[13.5px] leading-relaxed text-[#7A4A40]">
        Akunmu akan dinonaktifkan: kamu keluar dari semua perangkat dan tidak bisa masuk lagi dengan akun ini.
      </p>
      <div class="mb-[18px] flex items-start gap-[11px] rounded-xl border border-danger-border bg-white px-4 py-3.5">
        <ClockIcon size="17px" class="flex-none text-[#8A2818]" />
        <div class="text-[13px] leading-[1.55] text-[#5B4A44]">
          <b class="text-brand-navy">Masih bisa dikembalikan oleh admin.</b> Akun dipindahkan ke tempat sampah admin,
          bukan langsung dihapus permanen. Hubungi admin NearBy lewat Pusat Bantuan bila ingin memulihkannya.
        </div>
      </div>
      <label class="mb-1.5 block text-[13px] font-bold text-brand-navy" for="del-password">Kata sandi</label>
      <input
        id="del-password"
        v-model="password"
        type="password"
        autocomplete="current-password"
        class="mb-3.5 w-full max-w-[280px] rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy"
      />
      <label class="mb-1.5 block text-[13px] font-bold text-brand-navy" for="del-confirm">Ketik <b>HAPUS</b> untuk konfirmasi</label>
      <input
        id="del-confirm"
        v-model="delConfirm"
        placeholder="HAPUS"
        class="mb-[18px] w-full max-w-[280px] rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy"
      />
      <p v-if="account.deleteError" class="mb-3 text-[13px] font-semibold text-danger">{{ account.deleteError }}</p>
      <div>
        <button
          type="button"
          class="rounded-[11px] px-6 py-3 font-extrabold"
          :class="canDelete ? 'cursor-pointer bg-danger text-white' : 'cursor-not-allowed bg-[#E7C9C1] text-white'"
          :disabled="!canDelete || account.deleting"
          @click="confirmDelete"
        >
          {{ account.deleting ? 'Menghapus…' : 'Hapus akun saya' }}
        </button>
      </div>
    </template>
  </div>
</template>
