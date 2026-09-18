<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAccountStore } from '@/stores/account'
import type { AccountSession } from '@/types'
import ToggleSwitch from '@/components/shared/ToggleSwitch.vue'
import ComputerIcon from '@/components/shared/ComputerIcon.vue'
import MobileIcon from '@/components/shared/MobileIcon.vue'

const account = useAccountStore()

const revoking = ref<number | null>(null)

async function endSession(session: AccountSession) {
  if (!confirm(`Keluarkan ${session.device}? Perangkat itu harus masuk lagi.`)) return
  revoking.value = session.id
  try {
    await account.revokeSession(session.id)
  } finally {
    revoking.value = null
  }
}

const pwOld = ref('')
const pwNew = ref('')
const pwConfirm = ref('')

async function submitPassword() {
  const ok = await account.changePassword(pwOld.value, pwNew.value, pwConfirm.value)
  if (ok) {
    pwOld.value = ''
    pwNew.value = ''
    pwConfirm.value = ''
  }
}

onMounted(() => account.fetchSessions())
</script>

<template>
  <div class="mb-[18px] rounded-2xl border border-border-card bg-white px-5 py-2">
    <div class="flex items-center gap-3.5 border-b border-[#F4EFE4] py-4 last:border-b-0">
      <div class="flex-1">
        <div class="text-sm font-extrabold text-brand-navy">Verifikasi dua langkah</div>
        <!-- Honest label: nothing behind this toggle yet, and pretending
             otherwise on a security screen is worse than saying so. -->
        <div class="mt-0.5 text-[12.5px] text-text-faint">Belum tersedia - sedang disiapkan</div>
      </div>
      <ToggleSwitch v-model="account.security.twofa" :disabled="true" label="Verifikasi dua langkah" />
    </div>
  </div>

  <div class="mb-2.5 text-[14.5px] font-extrabold text-brand-navy">Perangkat yang sedang masuk</div>

  <div v-if="account.sessionsLoading && !account.sessions.length" class="rounded-2xl border border-border-card bg-white px-5 py-8 text-center text-[13.5px] text-text-faint">
    Memuat…
  </div>

  <div v-else-if="!account.sessions.length" class="rounded-2xl border border-border-card bg-white px-5 py-8 text-center text-[13.5px] text-text-faint">
    Tidak ada perangkat lain yang tercatat.
  </div>

  <div v-else class="rounded-2xl border border-border-card bg-white px-5 py-2">
    <div
      v-for="s in account.sessions"
      :key="s.id"
      class="flex items-center gap-3.5 border-b border-[#F4EFE4] py-3.5 last:border-b-0"
    >
      <div class="flex h-[38px] w-[38px] flex-none items-center justify-center rounded-[10px] bg-[#EEF2F7] text-[17px] text-brand-navy">
        <MobileIcon v-if="s.mobile" size="18px" />
        <ComputerIcon v-else size="18px" />
      </div>
      <div class="min-w-0 flex-1">
        <div class="text-[13.5px] font-bold text-brand-navy">{{ s.device }}</div>
        <div class="text-xs text-text-faint">
          Masuk {{ s.createdAt }} · terakhir aktif {{ s.lastUsed }}
        </div>
      </div>
      <span v-if="s.current" class="rounded-full bg-teal-tint px-[11px] py-1 text-[11.5px] font-bold whitespace-nowrap text-teal-deep">
        Perangkat ini
      </span>
      <button
        v-else
        type="button"
        class="rounded-[9px] border border-danger-border px-[13px] py-1.5 text-[12.5px] font-bold whitespace-nowrap text-danger disabled:opacity-60"
        :disabled="revoking === s.id"
        @click="endSession(s)"
      >
        {{ revoking === s.id ? 'Mengeluarkan…' : 'Keluar' }}
      </button>
    </div>
  </div>

  <p v-if="account.sessionsError" class="mt-2 text-[12.5px] leading-relaxed font-semibold text-danger" role="alert">
    {{ account.sessionsError }}
  </p>

  <div class="mt-[22px] mb-2.5 text-[14.5px] font-extrabold text-brand-navy">Ganti kata sandi</div>
  <div class="max-w-[460px] rounded-2xl border border-border-card bg-white p-[22px]">
    <label for="pw-old" class="mb-1.5 block text-[13px] font-bold text-brand-navy">Kata sandi saat ini</label>
    <input
      id="pw-old"
      v-model="pwOld"
      type="password"
      autocomplete="current-password"
      placeholder="••••••••"
      class="mb-3.5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy"
    />
    <label for="pw-new" class="mb-1.5 block text-[13px] font-bold text-brand-navy">Kata sandi baru</label>
    <input
      id="pw-new"
      v-model="pwNew"
      type="password"
      autocomplete="new-password"
      placeholder="Minimal 8 karakter"
      class="mb-3.5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy"
    />
    <label for="pw-confirm" class="mb-1.5 block text-[13px] font-bold text-brand-navy">Ulangi kata sandi baru</label>
    <input
      id="pw-confirm"
      v-model="pwConfirm"
      type="password"
      autocomplete="new-password"
      placeholder="Ketik ulang kata sandi baru"
      class="mb-[18px] w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy"
      @keyup.enter="submitPassword"
    />
    <button
      type="button"
      class="rounded-[11px] bg-brand-blue px-[26px] py-3 font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-60"
      :disabled="account.passwordSaving"
      @click="submitPassword"
    >
      {{ account.passwordSaving ? 'Memperbarui…' : 'Perbarui kata sandi' }}
    </button>

    <p v-if="account.passwordError" class="mt-3 text-[13px] leading-relaxed font-semibold text-danger" role="alert">
      {{ account.passwordError }}
    </p>
    <p v-else-if="account.passwordSaved" class="mt-3 text-[13px] leading-relaxed font-semibold text-teal-deep" role="status">
      Kata sandi berhasil diperbarui. Perangkat lain sudah dikeluarkan.
    </p>
  </div>
</template>
