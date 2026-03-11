<script setup>
defineProps({
  severity: { type: String, required: true },
})

const styles = {
  high: 'bg-error/15 text-error',
  medium: 'bg-warning/15 text-warning',
  low: 'bg-warning/25 text-warning',
  safe: 'bg-success/15 text-success',
}
</script>

<template>
  <span
    class="inline-block px-2 py-0.5 text-xs font-medium rounded-full capitalize"
    :class="styles[severity] || styles.safe"
  >
    {{ severity }}
  </span>
</template>
