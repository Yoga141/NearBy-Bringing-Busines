<script setup lang="ts">
const props = withDefaults(
  defineProps<{ modelValue: boolean; tone?: 'blue' | 'teal'; disabled?: boolean; label?: string }>(),
  { tone: 'blue', disabled: false },
)
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

function toggle() {
  if (props.disabled) return
  emit('update:modelValue', !props.modelValue)
}
</script>

<template>
  <!--
    role="switch" + keyboard handling: these toggles sit in the accessibility
    panel itself, so reaching them with a keyboard and hearing their state in a
    screen reader is the difference between the panel working and not.
  -->
  <div
    class="relative h-[26px] w-11 flex-none rounded-full transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-brand-blue focus-visible:ring-offset-2 focus-visible:outline-none"
    :class="[
      modelValue ? (tone === 'teal' ? 'bg-teal' : 'bg-brand-blue') : 'bg-[#D8D0C0]',
      disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
    ]"
    role="switch"
    :tabindex="disabled ? -1 : 0"
    :aria-checked="modelValue"
    :aria-disabled="disabled"
    :aria-label="label"
    @click="toggle"
    @keydown.enter.prevent="toggle"
    @keydown.space.prevent="toggle"
  >
    <div
      class="absolute top-[3px] h-5 w-5 rounded-full bg-white shadow-[0_1px_3px_rgba(0,0,0,.22)] transition-[left] duration-150"
      :class="modelValue ? 'left-[21px]' : 'left-[3px]'"
    />
  </div>
</template>
