<script setup>
import { ref, computed } from 'vue'
import { formatTime } from '@/utils/formatters.js'
import { getSeverityColor } from '@/utils/colors.js'

const props = defineProps({
  segments: { type: Array, required: true },
  duration: { type: Number, required: true },
})

const hoveredSegment = ref(null)

const timeLabels = computed(() => {
  if (props.duration <= 0) return []
  const count = 6
  const labels = []
  for (let i = 0; i < count; i++) {
    const t = (i * props.duration) / (count - 1)
    labels.push(t)
  }
  return labels
})

function segmentStyle(seg) {
  const left = (seg.startTime / props.duration) * 100
  const width = ((seg.endTime - seg.startTime) / props.duration) * 100
  return { left: `${left}%`, width: `${Math.max(width, 0.5)}%` }
}

function severityFill(severity) {
  return getSeverityColor(severity)
}

function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1)
}
</script>

<template>
  <div class="rounded-2xl border border-line bg-surface p-3 sm:p-4 md:p-5">
    <h3 class="mb-4 text-heading font-semibold">Segment Timeline</h3>

    <div class="relative overflow-x-auto">
      <!-- Time labels above bar -->
      <div class="mb-2 flex justify-between text-muted min-w-0" style="font-size: 0.7rem">
        <span v-for="t in timeLabels" :key="t">{{ formatTime(t) }}</span>
      </div>

      <!-- Timeline bar -->
      <div class="relative h-10 overflow-hidden rounded-lg bg-surface-alt">
        <div
          v-for="(seg, i) in segments"
          :key="i"
          class="absolute top-0 h-full cursor-pointer transition-opacity"
          :style="{
            ...segmentStyle(seg),
            backgroundColor: severityFill(seg.severity),
            opacity: hoveredSegment === seg ? 1 : 0.6,
          }"
          @mouseenter="hoveredSegment = seg"
          @mouseleave="hoveredSegment = null"
        >
          <!-- Tooltip -->
          <div
            v-if="hoveredSegment === seg"
            class="absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-lg border border-line bg-surface px-3 py-2 shadow-xl"
            style="font-size: 0.75rem"
          >
            <p class="font-medium text-heading capitalize">
              {{ seg.type }} &middot; {{ capitalize(seg.severity) }} Severity
            </p>
            <p class="text-muted">
              {{ formatTime(seg.startTime) }} &rarr; {{ formatTime(seg.endTime) }} &middot; {{ seg.metricValue.toFixed(2) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div class="mt-3 flex gap-5" style="font-size: 0.7rem">
        <span class="flex items-center gap-1.5 text-muted">
          <span class="h-2 w-2 rounded-sm bg-error" /> High
        </span>
        <span class="flex items-center gap-1.5 text-muted">
          <span class="h-2 w-2 rounded-sm bg-warning" /> Medium
        </span>
        <span class="flex items-center gap-1.5 text-muted">
          <span class="h-2 w-2 rounded-sm" style="background-color: #eab308" /> Low
        </span>
      </div>
    </div>
  </div>
</template>
