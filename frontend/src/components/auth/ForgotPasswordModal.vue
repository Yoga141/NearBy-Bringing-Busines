<script setup lang="ts">
import { ref } from 'vue'
import { useDashboardStore } from '@/stores/dashboard'
import CloseIcon from '@/components/shared/CloseIcon.vue'
import CheckIcon from '@/components/shared/CheckIcon.vue'

/**
 * "Lupa password".
 *
 * NearBy has no mail service, so there is no reset link to send. The request
 * goes to the admin instead (stored as a question, "Dashboard → Pertanyaan"),
 * together with a contact; the admin creates a temporary password from
 * "Pengguna → Kelola" and passes it on.
 */
const props = defineProps<{ initialEmail: string }>()
const emit = defineEmits<{ close: [] }>()

const dashboard = useDashboardStore()

const email = ref(props.initialEmail)
const contact = ref('')
const sent = ref(false)
const sending = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
    error.value = 'Masukkan alamat email akunmu yang valid.'
    return
  }
  if (contact.value.trim().length < 5) {
    error.value = 'Isi nomor WhatsApp atau kontak lain supaya admin bisa menghubungimu.'
    return
  }
  sending.value = true
  const ok = await dashboard.submitQuestion(
    `Permintaan reset kata sandi untuk akun ${email.value.trim()}.`,
    '',
    contact.value.trim(),
  )
  sending.value = false
  if (ok) sent.value = true
  else error.value = 'Permintaan gagal dikirim. Periksa koneksi lalu coba lagi.'
}
</script>

<template>
  <div
    class="fixed inset-0 z-[97] flex items-center justify-center bg-[rgba(11,20,30,.55)] p-6 backdrop-blur-[3px]"
    @click="emit('close')"
  >
    <div class="w-[420px] max-w-full overflow-hidden rounded-2xl bg-white shadow-[0_30px_80px_rgba(9,24,40,.42)]" @click.stop>
      <div class="flex items-center justify-between bg-brand-navy px-[22px] py-[18px] text-white">
        <div class="text-base font-extrabold">Lupa password</div>
        <button type="button" aria-label="Tutup" class="text-[#AFC3DC]" @click="emit('close')"><CloseIcon size="20px" /></button>
      </div>

      <div v-if="!sent" class="p-[22px]">
        <p class="mb-4 text-[13.5px] leading-relaxed text-[#5B6470]">
          Isi email akunmu dan kontak yang bisa dihubungi. Admin NearBy akan membuatkan kata sandi sementara dan
          mengirimkannya ke kontak tersebut.
        </p>
        <label class="mb-1.5 block text-[13px] font-bold text-brand-navy" for="forgot-email">Email akun</label>
        <input
          id="forgot-email"
          v-model="email"
          type="email"
          placeholder="nama@email.com"
          class="mb-3.5 w-full rounded-[11px] border border-border-input px-3.5 py-3 text-brand-navy"
        />
        <label class="mb-1.5 block text-[13px] font-bold text-brand-navy" for="forgot-contact">Nomor WhatsApp / kontak</label>
        <input
          id="forgot-contact"
          v-model="contact"
          placeholder="0812-xxxx-xxxx"
          class="mb-3 w-full rounded-[11px] border border-border-input px-3.5 py-3 text-brand-navy"
        />
        <p v-if="error" class="mb-3 text-[13px] font-semibold text-danger">{{ error }}</p>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-[11px] border border-border-input px-4 py-2.5 font-bold text-text-muted" @click="emit('close')">
            Batal
          </button>
          <button
            type="button"
            class="rounded-[11px] bg-brand-blue px-5 py-2.5 font-extrabold text-white disabled:opacity-60"
            :disabled="sending"
            @click="submit"
          >
            {{ sending ? 'Mengirim…' : 'Kirim permintaan' }}
          </button>
        </div>
      </div>

      <div v-else class="p-[22px] pt-[26px] text-center">
        <div class="mx-auto mb-3.5 flex h-14 w-14 items-center justify-center rounded-full bg-teal-tint text-teal-deep"><CheckIcon size="24px" /></div>
        <div class="text-[17px] font-extrabold text-brand-navy">Permintaan terkirim</div>
        <p class="mt-2 text-[13.5px] leading-relaxed text-[#5B6470]">
          Admin akan menghubungi <b class="text-brand-navy">{{ contact }}</b> dengan kata sandi sementara untuk
          <b class="text-brand-navy">{{ email }}</b>. Setelah masuk, ganti kata sandi di menu Akun → Keamanan.
        </p>
        <div class="mt-[18px] flex justify-center">
          <button type="button" class="rounded-[11px] bg-brand-blue px-[22px] py-2.5 font-extrabold text-white" @click="emit('close')">
            Mengerti
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
