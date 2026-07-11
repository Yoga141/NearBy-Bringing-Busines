<script setup lang="ts">
import type { Role } from '@/types'

defineProps<{ modelValue: Role }>()
const emit = defineEmits<{ 'update:modelValue': [value: Role] }>()

const options: { value: Role; emoji: string; label: string; desc: string }[] = [
  { value: 'user', emoji: '👤', label: 'Pengguna biasa', desc: 'Beri komentar & rating pada UMKM' },
  { value: 'owner', emoji: '🏪', label: 'Pemilik UMKM', desc: 'Daftarkan & kelola usahamu sendiri' },
]
</script>

<template>
  <div class="mb-5 grid grid-cols-2 gap-[11px]">
    <div
      v-for="opt in options"
      :key="opt.value"
      class="cursor-pointer rounded-[14px] border-2 p-4 px-3 text-center transition-all duration-100"
      :class="
        modelValue === opt.value
          ? 'border-brand-blue bg-brand-blue-tint-2 shadow-[0_6px_16px_rgba(44,94,173,.14)]'
          : 'border-border-role bg-white'
      "
      @click="emit('update:modelValue', opt.value)"
    >
      <div class="text-xl">{{ opt.emoji }}</div>
      <div class="mt-[7px] text-[14.5px] font-extrabold">{{ opt.label }}</div>
      <div class="mt-[3px] text-xs leading-[1.4] text-[#7A8089]">{{ opt.desc }}</div>
    </div>
  </div>
</template>
