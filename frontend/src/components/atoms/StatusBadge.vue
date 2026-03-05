<script setup>
defineProps({
  status: { type: String, required: true },
})

const styles = {
  queued: 'bg-surface-alt text-body',
  processing: 'bg-warning/15 text-warning animate-pulse',
  completed: 'bg-success/15 text-success',
  failed: 'bg-error/15 text-error',
}
</script>

<template>
  <span
    class="inline-block px-2 py-0.5 text-xs font-medium rounded-full capitalize"
    :class="styles[status] || styles.queued"
  >
    {{ status }}
  </span>
</template>
