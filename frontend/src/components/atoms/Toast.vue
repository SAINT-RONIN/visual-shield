<template>
  <div :class="['flex items-center gap-3 rounded-lg border-l-4 bg-surface p-4 shadow-lg', borderClass]">
    <span :class="['text-lg leading-none select-none', iconClass]" aria-hidden="true">{{ icon }}</span>
    <p class="flex-1 text-sm text-body">{{ message }}</p>
    <button
      class="text-muted hover:text-heading transition-colors text-xl leading-none"
      aria-label="Dismiss"
      @click="emit('dismiss', id)"
    >
      &times;
    </button>
  </div>
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
  if (props.type === 'success') return 'Ã¢Å“â€œ'
  if (props.type === 'error') return 'Ã¢Å“â€¢'
  return 'Ã¢â€žÂ¹'
})
</script>
