<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import {
  formatLatLng,
  googleMapsPinUrl,
  isWithinBalikpapan,
  osmEmbedUrl,
  parseErrorMessage,
  parseLocationInput,
  type LatLng,
} from '@/lib/geo'

/**
 * Map pin for a UMKM: paste coordinates or a full Google Maps / OSM link,
 * or use the device's GPS. The value is only emitted once it parses and
 * lies inside Kota Balikpapan; `invalid` tells the form to block saving
 * while the text box holds something unusable.
 */
const props = defineProps<{ modelValue: LatLng | null }>()
const emit = defineEmits<{ 'update:modelValue': [LatLng | null]; 'update:invalid': [boolean] }>()

const text = ref('')
const error = ref('')
const locating = ref(false)

watch(
  () => props.modelValue,
  (value) => {
    // Don't overwrite what the user is typing when the parent echoes our own emit back.
    const current = parseLocationInput(text.value)
    if (value && current.ok && current.point.lat === value.lat && current.point.lng === value.lng) return
    text.value = value ? formatLatLng(value) : ''
    error.value = ''
    emit('update:invalid', false)
  },
  { immediate: true },
)

function onInput() {
  const result = parseLocationInput(text.value)
  if (result.ok) {
    error.value = ''
    emit('update:invalid', false)
    emit('update:modelValue', result.point)
  } else if (result.reason === 'empty') {
    error.value = ''
    emit('update:invalid', false)
    emit('update:modelValue', null)
  } else {
    error.value = parseErrorMessage(result)
    emit('update:invalid', true)
  }
}

function clearPin() {
  text.value = ''
  onInput()
}

function useMyLocation() {
  if (!('geolocation' in navigator)) {
    error.value = 'Peramban ini tidak mendukung deteksi lokasi.'
    return
  }
  locating.value = true
  error.value = ''
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      locating.value = false
      const point = { lat: pos.coords.latitude, lng: pos.coords.longitude }
      if (!isWithinBalikpapan(point)) {
        error.value = `Lokasi perangkatmu (${formatLatLng(point)}) di luar Kota Balikpapan. Gunakan tombol ini saat berada di lokasi usaha, atau tempel koordinatnya.`
        return
      }
      text.value = formatLatLng(point)
      onInput()
    },
    (err) => {
      locating.value = false
      error.value =
        err.code === err.PERMISSION_DENIED
          ? 'Izin lokasi ditolak. Izinkan akses lokasi di peramban, atau tempel koordinat secara manual.'
          : 'Lokasi perangkat tidak bisa dibaca. Coba lagi atau tempel koordinat secara manual.'
    },
    { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
  )
}

const preview = computed(() => (props.modelValue && !error.value ? props.modelValue : null))
</script>

<template>
  <div class="mb-3.5">
    <label class="mb-1.5 block text-[13px] font-bold text-brand-navy dark:text-text-primary" for="umkm-pin">
      Titik lokasi di peta <span class="font-semibold text-text-faint">(opsional, disarankan)</span>
    </label>
    <div class="flex flex-col gap-2 mobile:flex-row">
      <input
        id="umkm-pin"
        v-model="text"
        inputmode="text"
        autocomplete="off"
        placeholder="-1.2654, 116.8312 atau link Google Maps lengkap"
        class="w-full min-w-0 rounded-xl border bg-white px-3.5 py-2.5 font-medium text-brand-navy placeholder:text-text-faint dark:bg-[#1a293a] dark:text-text-primary"
        :class="error ? 'border-danger' : 'border-border-input dark:border-[#2e4257]'"
        :aria-invalid="!!error"
        aria-describedby="umkm-pin-help"
        @input="onInput"
      />
      <button
        type="button"
        class="flex-none rounded-xl border border-border-input bg-white px-3.5 py-2.5 text-[13px] font-bold whitespace-nowrap text-brand-blue disabled:opacity-60 dark:bg-[#1a293a] dark:border-[#2e4257]"
        :disabled="locating"
        @click="useMyLocation"
      >
        {{ locating ? 'Mencari lokasi…' : 'Gunakan lokasi saya' }}
      </button>
    </div>
    <p v-if="error" class="mt-1.5 text-[12.5px] font-semibold text-danger" role="alert">{{ error }}</p>
    <p v-else id="umkm-pin-help" class="mt-1.5 text-[12px] text-text-faint">
      Di Google Maps, tekan lama titik usahamu lalu salin angka koordinat yang muncul. Titik harus berada di Kota Balikpapan.
    </p>

    <div v-if="preview" class="mt-2.5 overflow-hidden rounded-xl border border-border-card">
      <iframe
        :src="osmEmbedUrl(preview)"
        title="Pratinjau titik lokasi"
        class="block h-[180px] w-full border-0"
        loading="lazy"
        referrerpolicy="no-referrer"
      />
      <div class="flex items-center justify-between gap-2 bg-[#FBF8F2] px-3 py-2 text-[12px] dark:bg-[#16222f]">
        <a :href="googleMapsPinUrl(preview)" target="_blank" rel="noopener" class="font-bold text-brand-blue">Cek di Google Maps</a>
        <button type="button" class="font-bold text-danger" @click="clearPin">Hapus titik</button>
      </div>
    </div>
  </div>
</template>
