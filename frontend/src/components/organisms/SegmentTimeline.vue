<script setup>
import { ref, computed } from 'vue'
import { formatTime } from '@/utils/formatters.js'

const props = defineProps({
  segments: { type: Array, required: true },
  duration: { type: Number, required: true },
})

const hoveredSegment = ref(null)

const timeLabels = computed(() => {
  if (props.duration <= 0) return []

  let step = 30
  if (props.duration <= 30) step = 5
  else if (props.duration <= 120) step = 10

  const labels = []
  for (let t = 0; t <= props.duration; t += step) {
    labels.push(t)
  }
  return labels
})

function segmentStyle(seg) {
  const left = (seg.startTime / props.duration) * 100
  const width = ((seg.endTime - seg.startTime) / props.duration) * 100
  return { left: `${left}%`, width: `${Math.max(width, 0.5)}%` }
}

function segmentColor(severity) {
  if (severity === 'high') return 'bg-error'
  if (severity === 'medium') return 'bg-warning'
  return 'bg-warning/60'
}
</script>

<template>
  <div class="bg-surface rounded-xl p-4 md:p-6 border border-line">
    <h3 class="text-base md:text-lg font-semibold text-heading mb-4">Risk Timeline</h3>

    <div class="relative">
      <div class="bg-surface-alt rounded-full h-8 relative overflow-hidden">
        <div
          v-for="(seg, i) in segments"
          :key="i"
          class="absolute top-0 h-full opacity-80 cursor-pointer transition-opacity hover:opacity-100"
          :class="segmentColor(seg.severity)"
          :style="segmentStyle(seg)"
          @mouseenter="hoveredSegment = seg"
          @mouseleave="hoveredSegment = null"
        />
      </div>

      <!-- Time labels -->
      <div class="flex justify-between mt-2 text-xs text-muted">
        <span v-for="t in timeLabels" :key="t">{{ formatTime(t) }}</span>
      </div>

      <!-- Tooltip -->
      <div
        v-if="hoveredSegment"
        class="absolute -top-16 left-1/2 -translate-x-1/2 bg-surface-alt border border-line rounded-lg px-3 py-2 text-xs text-body shadow-lg z-10 whitespace-nowrap"
      >
        <p class="font-medium text-heading capitalize">{{ hoveredSegment.type }}</p>
        <p>{{ formatTime(hoveredSegment.startTime) }} - {{ formatTime(hoveredSegment.endTime) }}</p>
        <p class="capitalize">Severity: {{ hoveredSegment.severity }}</p>
      </div>
    </div>

    <!-- Legend -->
    <div class="flex gap-6 mt-4 text-xs text-muted">
      <span class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full bg-error" /> High
      </span>
      <span class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full bg-warning" /> Medium
      </span>
      <span class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full bg-warning/60" /> Low
      </span>
    </div>
  </div>
</template>
