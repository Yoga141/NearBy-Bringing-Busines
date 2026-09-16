<script setup lang="ts">
/**
 * Avatar plus the controls to change it.
 *
 * One component for all three profile screens (/akun, the account settings
 * modal, and the dashboard profile card) — they showed the same avatar and the
 * same dead "Ganti foto" button, so the working version lives in one place.
 *
 * The <input type="file"> is visually hidden and driven by the button: a raw
 * file input cannot be styled to match the rest of the forms, but hiding it
 * this way keeps it reachable by keyboard and screen readers, which
 * `display: none` would not.
 */
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import UserAvatar from '@/components/shared/UserAvatar.vue'

const props = withDefaults(defineProps<{ size?: 'sm' | 'md' }>(), { size: 'md' })

const auth = useAuthStore()
const fileInput = ref<HTMLInputElement | null>(null)
const localError = ref('')

const MAX_BYTES = 2 * 1024 * 1024
const ACCEPT = 'image/jpeg,image/png,image/webp'

const avatarClass = computed(() =>
  props.size === 'sm'
    ? 'flex h-[60px] w-[60px] flex-none items-center justify-center rounded-full bg-brand-navy text-[22px] font-extrabold text-white'
    : 'flex h-16 w-16 flex-none items-center justify-center rounded-full bg-brand-navy text-2xl font-extrabold text-white',
)

const message = computed(() => localError.value || auth.photoError)

async function onPick(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  // Reset straight away so picking the same file twice still fires a change
  // event — otherwise a failed upload can't be retried with the same photo.
  input.value = ''
  if (!file) return

  localError.value = ''

  // Catch the two obvious cases here rather than after a round trip.
  if (!ACCEPT.split(',').includes(file.type)) {
    localError.value = 'Format foto harus JPG, PNG, atau WebP.'
    return
  }
  if (file.size > MAX_BYTES) {
    localError.value = `Ukuran foto ${(file.size / 1024 / 1024).toFixed(1).replace('.', ',')} MB melebihi batas 2 MB.`
    return
  }

  await auth.uploadPhoto(file)
}

async function onRemove() {
  if (!confirm('Hapus foto profil? Avatar akan kembali memakai huruf awal namamu.')) return
  localError.value = ''
  await auth.removePhoto()
}
</script>

<template>
  <div>
    <div class="flex items-center gap-[15px]">
      <UserAvatar
        :class="avatarClass"
        :src="auth.user?.avatarUrl"
        :initial="auth.authInitial"
        :name="auth.user?.name"
      />

      <div class="min-w-0">
        <slot name="meta" />

        <input
          ref="fileInput"
          type="file"
          :accept="ACCEPT"
          class="sr-only"
          :disabled="auth.photoUploading"
          @change="onPick"
        />

        <div class="mt-2 flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-[10px] border border-border-input px-3.5 py-2 text-[13px] font-bold text-brand-navy transition-colors duration-150 hover:bg-brand-blue-tint disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="auth.photoUploading"
            @click="fileInput?.click()"
          >
            {{ auth.photoUploading ? 'Mengunggah…' : auth.user?.avatarUrl ? 'Ganti foto' : 'Unggah foto' }}
          </button>
          <button
            v-if="auth.user?.avatarUrl"
            type="button"
            class="rounded-[10px] border border-border-input px-3.5 py-2 text-[13px] font-bold text-danger transition-colors duration-150 hover:bg-danger-tint disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="auth.photoUploading"
            @click="onRemove"
          >
            Hapus
          </button>
        </div>
      </div>
    </div>

    <p v-if="message" class="mt-2.5 text-[12.5px] leading-relaxed font-semibold text-danger" role="alert">
      {{ message }}
    </p>
    <p v-else class="mt-2.5 text-[12px] leading-relaxed text-text-faint">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
  </div>
</template>
