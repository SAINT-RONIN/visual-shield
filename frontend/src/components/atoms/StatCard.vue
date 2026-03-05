<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], required: true },
  colorClass: { type: String, default: 'text-heading' },
})
</script>

<template>
  <div class="bg-surface rounded-xl p-4 md:p-5 border border-line">
    <div class="text-muted mb-2">
      <slot name="icon" />
    </div>
    <p class="text-2xl md:text-3xl lg:text-4xl font-bold" :class="colorClass">
      {{ value }}
    </p>
    <p class="text-xs md:text-sm text-muted mt-1">{{ label }}</p>
  </div>
</template>
