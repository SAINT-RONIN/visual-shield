<script setup>
// Atom: StatCard presents one summary metric with a label so report stats stay visually consistent.
defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], required: true },
  color: { type: String, default: 'currentColor' },
})
</script>

<template>
  <div class="bg-surface rounded-2xl p-5 border border-line" :style="{ '--stat-color': color }">
    <div class="flex items-center justify-between mb-3">
      <span class="text-muted text-xs">{{ label }}</span>
      <div class="stat-icon-bg flex h-8 w-8 items-center justify-center rounded-lg">
        <span class="stat-icon flex items-center justify-center">
          <slot name="icon" />
        </span>
      </div>
    </div>
    <p class="stat-value text-xl sm:text-2xl md:text-3xl font-semibold">
      {{ value }}
    </p>
  </div>
</template>

<style scoped>
.stat-icon-bg {
  background-color: color-mix(in srgb, var(--stat-color) 10%, transparent);
}
.stat-icon {
  color: var(--stat-color);
}
.stat-value {
  color: var(--stat-color);
}
</style>
