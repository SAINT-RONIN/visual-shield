<template>
  <article :class="['flex items-center gap-3 rounded-lg border-l-4 bg-surface p-4 shadow-lg', borderClass]" role="alert" aria-live="assertive">
    <span :class="['text-sm leading-none select-none font-medium', iconClass]" aria-hidden="true">{{ icon }}</span>
    <p class="flex-1 text-sm text-body">{{ message }}</p>
    <button
      class="text-muted hover:text-heading transition-colors text-xl leading-none rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
      aria-label="Dismiss"
      @click="emit('dismiss', id)"
    >
      &times;
    </button>
  </article>
</template>

<script setup>
// Atom: Toast renders one dismissible notification item inside the global toast stack.
import { computed } from 'vue'

const props = defineProps({
  message: { type: String, required: true },
  type: { type: String, default: 'info' },
  id: { type: Number, required: true },
})

const emit = defineEmits(['dismiss'])

const borderClass = computed(() => ({
  'border-success': props.type === 'success',
  'border-error': props.type === 'error',
  'border-primary': props.type === 'info',
}))

const iconClass = computed(() => ({
  'text-success': props.type === 'success',
  'text-error': props.type === 'error',
  'text-primary': props.type === 'info',
}))

const icon = computed(() => {
  if (props.type === 'success') return '[OK]'
  if (props.type === 'error') return '[!]'
  return '[i]'
})
</script>
