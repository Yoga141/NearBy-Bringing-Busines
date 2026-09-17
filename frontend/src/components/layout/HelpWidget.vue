<script setup lang="ts">
import { ref } from 'vue'
import { useUiStore } from '@/stores/ui'
import { useDashboardStore } from '@/stores/dashboard'
import CloseIcon from '@/components/shared/CloseIcon.vue'

const ui = useUiStore()
const dashboard = useDashboardStore()

const askText = ref('')
const askName = ref('')
const askContact = ref('')
const bugKind = ref('Tampilan / UI')
const bugText = ref('')
const bugName = ref('')

const bugKinds = ['Tampilan / UI', 'Fungsi tidak jalan', 'Data UMKM salah', 'Login / akun', 'Lainnya']

async function submitAsk() {
  if (!askText.value.trim()) {
    alert('Mohon tulis pertanyaan kamu terlebih dahulu.')
    return
  }
  const ok = await dashboard.submitQuestion(askText.value.trim(), askName.value, askContact.value)
  if (!ok) {
    alert('Gagal mengirim pertanyaan. Periksa koneksimu dan coba lagi.')
    return
  }
  ui.helpOpen = false
  ui.helpTab = 'ask'
  askText.value = ''
  askName.value = ''
  askContact.value = ''
  // No in-app inbox or mail service exists yet, so the wording promises only
  // what actually happens: an admin reads it and replies via the contact given.
  alert('Terima kasih! Pertanyaan kamu sudah kami terima. Admin akan menghubungimu lewat kontak yang kamu isi.')
}

async function submitBug() {
  if (!bugText.value.trim()) {
    alert('Mohon isi deskripsi masalah terlebih dahulu.')
    return
  }
  const ok = await dashboard.submitProblemReport(bugKind.value, bugText.value.trim(), bugName.value)
  if (!ok) {
    alert('Gagal mengirim laporan. Periksa koneksimu dan coba lagi.')
    return
  }
  ui.helpOpen = false
  ui.helpTab = 'ask'
  bugText.value = ''
  bugName.value = ''
  alert('Terima kasih! Laporan masalah kamu sudah kami terima dan akan segera ditindaklanjuti.')
}
</script>

<template>
  <div class="fixed right-4 bottom-4 z-[75] flex flex-col items-end gap-3 mobile:right-[22px] mobile:bottom-[22px]">
    <div
      v-if="ui.helpOpen"
      class="w-[340px] max-w-[calc(100vw-44px)] overflow-hidden rounded-[18px] border border-border-card bg-white shadow-[0_22px_55px_rgba(9,24,40,.28)]"
    >
      <div class="flex items-center justify-between bg-brand-navy px-[18px] py-4 text-white">
        <div>
          <div class="text-[15.5px] font-extrabold">Pusat Bantuan</div>
          <div class="mt-px text-xs text-[#AFC3DC]">Bertanya &amp; laporkan masalah</div>
        </div>
        <button type="button" class="text-[#AFC3DC]" @click="ui.helpOpen = false"><CloseIcon size="20px" /></button>
      </div>

      <div class="flex bg-brand-navy px-4">
        <button
          type="button"
          class="flex-1 border-b-[2.5px] py-2.5 text-[13px] font-extrabold"
          :class="ui.helpTab === 'ask' ? 'border-white text-white' : 'border-transparent text-[#AFC3DC]'"
          @click="ui.helpTab = 'ask'"
        >
          Bertanya
        </button>
        <button
          type="button"
          class="flex-1 border-b-[2.5px] py-2.5 text-[13px] font-extrabold"
          :class="ui.helpTab === 'bug' ? 'border-white text-white' : 'border-transparent text-[#AFC3DC]'"
          @click="ui.helpTab = 'bug'"
        >
          Laporkan masalah
        </button>
      </div>

      <div v-if="ui.helpTab === 'ask'" class="px-[18px] pt-4 pb-[18px]">
        <div class="mb-3.5 text-[12.5px] leading-relaxed text-text-muted">
          Ada yang ingin ditanyakan? Kirim pertanyaanmu ke admin NearBy. Isi kontak agar kami bisa membalas.
        </div>
        <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Pertanyaan kamu</label>
        <textarea
          v-model="askText"
          placeholder="Tulis pertanyaan yang ingin kamu ajukan ke admin…"
          class="mb-3.5 min-h-[92px] w-full resize-y rounded-[11px] border border-border-input px-3.5 py-3 text-brand-navy"
        />
        <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Nama</label>
        <input v-model="askName" placeholder="Nama kamu" class="mb-3.5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy" />
        <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">
          Kontak <span class="font-semibold text-text-faint">(WhatsApp atau email)</span>
        </label>
        <input
          v-model="askContact"
          placeholder="Mis. 0812-3456-7890"
          class="mb-4 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy"
        />
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-[11px] border border-border-input px-4 py-2.5 font-bold text-text-muted" @click="ui.helpOpen = false">
            Batal
          </button>
          <button type="button" class="rounded-[11px] bg-brand-navy px-5 py-2.5 font-extrabold text-white" @click="submitAsk">
            Kirim pertanyaan
          </button>
        </div>
      </div>

      <div v-else class="px-[18px] pt-4 pb-[18px]">
        <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Jenis masalah</label>
        <select v-model="bugKind" class="mb-3.5 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 font-semibold text-brand-navy">
          <option v-for="k in bugKinds" :key="k">{{ k }}</option>
        </select>
        <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Deskripsi masalah</label>
        <textarea
          v-model="bugText"
          placeholder="Ceritakan apa yang terjadi dan langkah sebelum masalah muncul…"
          class="mb-3.5 min-h-[92px] w-full resize-y rounded-[11px] border border-border-input px-3.5 py-3 text-brand-navy"
        />
        <label class="mb-1.5 block text-[12.5px] font-bold text-brand-navy">Nama</label>
        <input v-model="bugName" placeholder="Nama kamu" class="mb-4 w-full rounded-[11px] border border-border-input bg-white px-3.5 py-2.5 text-brand-navy" />
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-[11px] border border-border-input px-4 py-2.5 font-bold text-text-muted" @click="ui.helpTab = 'ask'">
            Batal
          </button>
          <button type="button" class="rounded-[11px] bg-brand-navy px-5 py-2.5 font-extrabold text-white" @click="submitBug">
            Kirim laporan
          </button>
        </div>
      </div>
    </div>

    <button
      type="button"
      title="Bantuan & FAQ"
      aria-label="Bantuan"
      class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-navy text-xl font-extrabold text-white shadow-[0_12px_30px_rgba(19,50,77,.35)] transition-all hover:-translate-y-0.5 hover:bg-brand-blue-deep mobile:h-[58px] mobile:w-[58px] mobile:text-2xl"
      @click="ui.toggleHelp"
    >
      ?
    </button>
  </div>
</template>
