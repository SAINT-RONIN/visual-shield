<script setup>
defineProps({
  severity: { type: String, required: true },
})

const styles = {
  high: 'bg-red-500/15 text-red-400',
  medium: 'bg-orange-500/15 text-orange-400',
  low: 'bg-yellow-500/15 text-yellow-400',
  safe: 'bg-green-500/15 text-green-400',
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
