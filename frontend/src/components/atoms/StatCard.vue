<script setup>
defineProps({
  label: { type: String, required: true },
  value: { type: [Number, String], required: true },
  color: { type: String, default: '#ffffff' },
})
</script>

<template>
  <div class="bg-surface rounded-2xl p-5 border border-line">
    <div class="flex items-center justify-between mb-3">
      <span class="text-muted text-xs">{{ label }}</span>
      <div
        class="flex h-8 w-8 items-center justify-center rounded-lg"
        :style="{ backgroundColor: color + '1a' }"
      >
        <span :style="{ color: color }" class="flex items-center justify-center">
          <slot name="icon" />
        </span>
      </div>
    </div>
    <p class="text-xl sm:text-2xl md:text-3xl font-semibold" :style="{ color: color }">
      {{ value }}
    </p>
  </div>
</template>
