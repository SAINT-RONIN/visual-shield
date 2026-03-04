<script setup>
defineProps({
  variant: { type: String, default: 'primary' },
  loading: { type: Boolean, default: false },
  fullWidth: { type: Boolean, default: false },
})
</script>

<template>
  <button
    :disabled="loading"
    class="px-4 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
    :class="[
      variant === 'primary' && 'bg-primary hover:bg-primary-hover text-heading',
      variant === 'secondary' && 'bg-surface-alt hover:bg-surface-hover text-body border border-line-strong',
      variant === 'ghost' && 'text-body hover:text-heading',
      fullWidth && 'w-full',
    ]"
  >
    <slot />
  </button>
</template>
