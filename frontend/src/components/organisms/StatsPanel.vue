<script setup>
import { computed } from 'vue'
import StatCard from '@/components/atoms/StatCard.vue'
import { metricColors } from '@/utils/colors.js'

const props = defineProps({
  summary: { type: Object, required: true },
})

const FLASH_COLOR = metricColors.flash
const MOTION_COLOR = metricColors.motion
const SAMPLING_COLOR = metricColors.sampling

const highestFlashFrequency = computed(() => {
  const value = props.summary.highestFlashFrequency != null
    ? props.summary.highestFlashFrequency.toFixed(1)
    : '0.0'
  return value + ' Hz'
})

const averageMotionIntensity = computed(() => {
  const value = props.summary.averageMotionIntensity != null
    ? props.summary.averageMotionIntensity.toFixed(1)
    : '0.0'
  return value + '/255'
})

const samplingRate = computed(() => {
  const value = props.summary.effectiveSamplingRate || '--'
  return value + ' FPS'
})
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <StatCard
      label="Total Flash Events"
      :value="summary.totalFlashEvents"
      :color="FLASH_COLOR"
    >
      <template #icon>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
      </template>
    </StatCard>

    <StatCard
      label="Highest Flash Freq."
      :value="highestFlashFrequency"
      :color="FLASH_COLOR"
    >
      <template #icon>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M22 12h-4l-3 9L9 3l-3 9H2"/>
        </svg>
      </template>
    </StatCard>

    <StatCard
      label="Avg Motion Intensity"
      :value="averageMotionIntensity"
      :color="MOTION_COLOR"
    >
      <template #icon>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 9l4-4 4 4M5 15l4 4 4-4M15 9l4-4M15 15l4 4"/>
        </svg>
      </template>
    </StatCard>

    <StatCard
      label="Sampling Rate"
      :value="samplingRate"
      :color="SAMPLING_COLOR"
    >
      <template #icon>
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </template>
    </StatCard>
  </div>
</template>
