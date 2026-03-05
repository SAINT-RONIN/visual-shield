<script setup>
import { computed } from 'vue'
import SeverityBadge from '@/components/atoms/SeverityBadge.vue'

const props = defineProps({
  segments: { type: Array, required: true },
})

function formatTime(seconds) {
  const m = Math.floor(seconds / 60)
  const s = (seconds % 60).toFixed(1)
  return `${m}:${s.padStart(4, '0')}`
}

const hasSegments = computed(() => props.segments.length > 0)
</script>

<template>
  <div class="bg-surface rounded-xl p-4 md:p-6 border border-line">
    <h3 class="text-base md:text-lg font-semibold text-heading mb-4">Flagged Segments</h3>

    <div v-if="!hasSegments" class="text-green-400 text-sm py-4 text-center">
      No risk segments detected
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="text-xs text-muted uppercase tracking-wide border-b border-line">
            <th class="text-left py-2 pr-4">Start Time</th>
            <th class="text-left py-2 pr-4">End Time</th>
            <th class="text-left py-2 pr-4">Type</th>
            <th class="text-left py-2 pr-4">Severity</th>
            <th class="text-left py-2">Metric Value</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(seg, i) in segments"
            :key="i"
            class="border-b border-line/50 text-body"
            :class="i % 2 === 1 ? 'bg-surface-alt/50' : ''"
          >
            <td class="py-2 pr-4">{{ formatTime(seg.startTime) }}</td>
            <td class="py-2 pr-4">{{ formatTime(seg.endTime) }}</td>
            <td class="py-2 pr-4 capitalize">{{ seg.type }}</td>
            <td class="py-2 pr-4"><SeverityBadge :severity="seg.severity" /></td>
            <td class="py-2">{{ seg.metricValue.toFixed(2) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
