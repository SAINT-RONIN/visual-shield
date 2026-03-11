<script setup>
import { computed } from 'vue'
import StatCard from '@/components/atoms/StatCard.vue'

const props = defineProps({
  summary: { type: Object, required: true },
})

const flashEventsColor = computed(() => {
  const v = props.summary.totalFlashEvents
  if (v > 50) return 'text-error'
  if (v > 20) return 'text-warning'
  return 'text-success'
})

const flashFreqColor = computed(() => {
  const v = props.summary.highestFlashFrequency
  if (v > 10) return 'text-error'
  if (v > 5) return 'text-warning'
  if (v > 3) return 'text-warning'
  return 'text-success'
})

const motionColor = computed(() => {
  const v = props.summary.averageMotionIntensity
  if (v > 120) return 'text-error'
  if (v > 60) return 'text-warning'
  if (v > 30) return 'text-warning'
  return 'text-success'
})
</script>

<template>
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
    <StatCard
      label="Flash Events"
      :value="summary.totalFlashEvents"
      :color-class="flashEventsColor"
    >
      <template #icon>
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
      </template>
    </StatCard>

    <StatCard
      label="Peak Flash Rate (Hz)"
      :value="summary.highestFlashFrequency.toFixed(1)"
      :color-class="flashFreqColor"
    >
      <template #icon>
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-4l-3 9L9 3l-3 9H2"/>
        </svg>
      </template>
    </StatCard>

    <StatCard
      label="Avg Motion Intensity"
      :value="summary.averageMotionIntensity.toFixed(1)"
      :color-class="motionColor"
    >
      <template #icon>
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 9l4-4 4 4M5 15l4 4 4-4M15 9l4-4M15 15l4 4"/>
        </svg>
      </template>
    </StatCard>

    <StatCard
      label="Effective FPS"
      :value="summary.effectiveSamplingRate || '--'"
      color-class="text-heading"
    >
      <template #icon>
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M7 4v16M17 4v16M3 8h4M3 12h18M3 16h4M17 8h4M17 16h4M3 4h18v16H3z"/>
        </svg>
      </template>
    </StatCard>
  </div>
</template>
