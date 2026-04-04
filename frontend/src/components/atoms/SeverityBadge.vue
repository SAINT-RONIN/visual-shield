<script setup>
// Atom: SeverityBadge displays the risk severity level with consistent styling across reports and cards.
defineProps({
  severity: { type: String, required: true },
})

const styles = {
  high: { badge: 'bg-error/10 text-error', dot: 'bg-error' },
  medium: { badge: 'bg-warning/10 text-warning', dot: 'bg-warning' },
  low: { badge: 'bg-warning/10 text-warning', dot: 'bg-warning' },
  safe: { badge: 'bg-success/10 text-success', dot: 'bg-success' },
}
</script>

<template>
  <span
    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
    :class="(styles[severity] || styles.safe).badge"
  >
    <span
      class="h-1.5 w-1.5 rounded-full"
      :class="(styles[severity] || styles.safe).dot"
    />
    {{ severity }}
  </span>
</template>
