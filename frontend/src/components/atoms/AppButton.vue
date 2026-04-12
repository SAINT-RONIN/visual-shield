<script setup>
// Atom: AppButton is the shared clickable button used for primary, secondary, ghost, and danger actions.
defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: 'md' },
  loading: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  fullWidth: { type: Boolean, default: false },
})
</script>

<template>
  <button
    :disabled="loading || disabled"
    class="rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-1"
    :class="[
      size === 'sm' ? 'px-3 py-1 text-sm' : 'px-4 py-2',
      variant === 'primary' && 'bg-primary hover:bg-primary-hover text-heading',
      variant === 'secondary' && 'bg-surface-alt hover:bg-surface-hover text-body border border-line-strong',
      variant === 'danger' && 'bg-error/20 hover:bg-error/30 text-error',
      variant === 'ghost' && 'text-body hover:text-heading',
      fullWidth && 'w-full',
    ]"
  >
    <slot />
  </button>
</template>
