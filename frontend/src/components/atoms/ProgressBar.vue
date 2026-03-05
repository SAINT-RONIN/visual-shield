<script setup>
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
    <div class="w-full bg-surface-alt rounded-full h-2">
      <div
        class="bg-primary h-2 rounded-full transition-all"
        :style="{ width: value + '%' }"
      ></div>
    </div>
  </div>
</template>
