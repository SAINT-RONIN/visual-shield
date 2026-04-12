<script setup>
// Atom: ProgressBar shows a single progress value with a short label for uploads and video processing.
defineProps({
  value: { type: Number, default: 0 },
  label: { type: String, default: '' },
})
</script>

<template>
  <div class="space-y-2">
    <div class="flex justify-between text-sm">
      <span class="text-body">{{ label }}</span>
      <span class="text-heading">{{ value }}%</span>
    </div>
    <progress
      class="progress-bar w-full h-2 rounded-full"
      :value="value"
      max="100"
    >{{ value }}%</progress>
  </div>
</template>

<style scoped>
.progress-bar {
  appearance: none;
  border: none;
  background-color: var(--color-surface-alt);
  border-radius: 9999px;
}
.progress-bar::-webkit-progress-bar {
  background-color: var(--color-surface-alt);
  border-radius: 9999px;
}
.progress-bar::-webkit-progress-value {
  background-color: var(--color-primary);
  border-radius: 9999px;
  transition: width 0.3s;
}
.progress-bar::-moz-progress-bar {
  background-color: var(--color-primary);
  border-radius: 9999px;
}
</style>
