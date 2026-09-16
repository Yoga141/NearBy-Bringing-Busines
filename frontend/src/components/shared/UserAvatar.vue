<script setup lang="ts">
/**
 * One avatar: the uploaded photo when there is one, the name's initial when
 * there isn't.
 *
 * Sizing and colours stay with the caller — pass them as ordinary classes and
 * they land on the root element, exactly as they did before this component
 * existed. That keeps the six avatars around the app looking the way each of
 * their surroundings needs.
 */
defineProps<{
  /** Photo URL, or null/undefined to show the initial instead. */
  src?: string | null
  /** Fallback letter, already uppercased by the caller or the API. */
  initial?: string
  /** Whose photo this is, for screen readers. */
  name?: string
}>()
</script>

<template>
  <div class="overflow-hidden">
    <img
      v-if="src"
      :src="src"
      :alt="name ? `Foto profil ${name}` : 'Foto profil'"
      class="h-full w-full object-cover"
    />
    <template v-else>{{ initial }}</template>
  </div>
</template>
