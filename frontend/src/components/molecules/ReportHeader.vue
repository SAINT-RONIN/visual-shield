<script setup>
import { computed } from 'vue'
import SeverityBadge from '@/components/atoms/SeverityBadge.vue'

const props = defineProps({
  video: { type: Object, required: true },
  riskLevel: { type: String, required: true },
})

const formattedDuration = computed(() => {
  const sec = props.video.duration || 0
  const m = Math.floor(sec / 60)
  const s = Math.round(sec % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
})

const formattedDate = computed(() => {
  return new Date(props.video.uploadedAt).toLocaleDateString()
})
</script>

<template>
  <div class="bg-surface rounded-xl p-4 md:p-6 border border-line">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h2 class="text-lg md:text-xl lg:text-2xl font-bold text-heading">{{ video.originalName }}</h2>
        <div class="flex flex-wrap gap-4 mt-2 text-xs md:text-sm text-muted">
          <span>Uploaded {{ formattedDate }}</span>
          <span>Duration: {{ formattedDuration }}</span>
          <span>{{ video.effectiveSamplingRate }} fps</span>
        </div>
      </div>
      <SeverityBadge :severity="riskLevel" />
    </div>
  </div>
</template>
