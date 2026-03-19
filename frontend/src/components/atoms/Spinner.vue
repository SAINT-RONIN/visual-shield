<script setup>
import { computed } from 'vue'

const props = defineProps({
  size: { type: String, default: 'md' },
})

const sizeClass = computed(() => {
  if (props.size === 'sm') return 'w-5 h-5'
  if (props.size === 'lg') return 'w-12 h-12'
  return 'w-8 h-8'
})
</script>

<template>
  <div
    :class="['animate-spin rounded-full border-2 border-surface-alt border-t-primary', sizeClass]"
    role="status"
  >
    <span class="sr-only">Loading...</span>
  </div>
</template>
