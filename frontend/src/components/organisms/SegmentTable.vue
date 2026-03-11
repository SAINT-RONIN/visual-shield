<script setup>
import { computed } from 'vue'
import SeverityBadge from '@/components/atoms/SeverityBadge.vue'
import { formatTime } from '@/utils/formatters.js'

const props = defineProps({
  segments: { type: Array, required: true },
  sortField: { type: String, default: 'start_time' },
  sortOrder: { type: String, default: 'asc' },
})

const emit = defineEmits(['sort'])

const hasSegments = computed(() => props.segments.length > 0)

// Map from API snake_case keys to frontend column keys for sort indicator matching
const apiKeyMap = {
  startTime: 'start_time',
  endTime: 'end_time',
  type: 'type',
  severity: 'severity',
  metricValue: 'metric_value',
}

function handleSort(columnKey) {
  emit('sort', columnKey)
}

function isActiveSort(columnKey) {
  return props.sortField === apiKeyMap[columnKey]
}

const columns = [
  { key: 'startTime', label: 'Start Time' },
  { key: 'endTime', label: 'End Time' },
  { key: 'type', label: 'Type' },
  { key: 'severity', label: 'Severity' },
  { key: 'metricValue', label: 'Metric Value' },
]
</script>

<template>
  <div class="rounded-2xl border border-line bg-surface">
    <div class="border-b border-line p-3 sm:p-4 md:p-5">
      <h3 class="text-heading font-semibold text-sm sm:text-base">Flagged Segments</h3>
    </div>

    <div v-if="!hasSegments" class="text-success text-sm py-6 text-center">
      No risk segments detected
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full" style="font-size: 0.875rem">
        <thead>
          <tr class="border-b border-line text-left text-muted">
            <th
              v-for="col in columns"
              :key="col.key"
              class="cursor-pointer px-5 py-3 transition-colors hover:text-heading"
              style="font-weight: 500; font-size: 0.75rem"
              @click="handleSort(col.key)"
            >
              <span class="flex items-center gap-1.5">
                {{ col.label }}
                <!-- Sort icon: inactive -->
                <svg
                  v-if="!isActiveSort(col.key)"
                  class="h-3 w-3 opacity-30"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="m7 15 5 5 5-5" />
                  <path d="m7 9 5-5 5 5" />
                </svg>
                <!-- Sort icon: ascending -->
                <svg
                  v-else-if="sortOrder === 'asc'"
                  class="h-3 w-3"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="m18 15-6-6-6 6" />
                </svg>
                <!-- Sort icon: descending -->
                <svg
                  v-else
                  class="h-3 w-3"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="m6 9 6 6 6-6" />
                </svg>
              </span>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(seg, i) in segments"
            :key="i"
            class="border-b border-line transition-colors last:border-0 hover:bg-surface-alt/50"
          >
            <td class="px-5 py-3.5 text-heading">{{ formatTime(seg.startTime, true) }}</td>
            <td class="px-5 py-3.5 text-heading">{{ formatTime(seg.endTime, true) }}</td>
            <td class="px-5 py-3.5">
              <span class="flex items-center gap-1.5 text-heading">
                <!-- Flash icon (Zap) -->
                <svg
                  v-if="seg.type === 'flash'"
                  class="h-3.5 w-3.5 text-error"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" />
                </svg>
                <!-- Motion icon (Activity) -->
                <svg
                  v-else
                  class="h-3.5 w-3.5 text-warning"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                >
                  <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2" />
                </svg>
                <span class="capitalize">{{ seg.type }}</span>
              </span>
            </td>
            <td class="px-5 py-3.5">
              <SeverityBadge :severity="seg.severity" />
            </td>
            <td class="px-5 py-3.5 font-mono text-heading" style="font-size: 0.8125rem">
              {{ seg.metricValue.toFixed(2) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
