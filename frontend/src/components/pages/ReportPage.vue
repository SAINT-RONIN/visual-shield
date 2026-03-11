<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { getAuthToken } from '@/utils/api.js'
import { useConfig } from '@/composables/useConfig.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import ReportHeader from '@/components/molecules/ReportHeader.vue'
import StatsPanel from '@/components/organisms/StatsPanel.vue'
import FlashFrequencyChart from '@/components/organisms/FlashFrequencyChart.vue'
import MotionIntensityChart from '@/components/organisms/MotionIntensityChart.vue'
import LuminanceChart from '@/components/organisms/LuminanceChart.vue'
import SegmentTimeline from '@/components/organisms/SegmentTimeline.vue'
import SegmentTable from '@/components/organisms/SegmentTable.vue'
import ExportButtons from '@/components/molecules/ExportButtons.vue'
import VideoOverlay from '@/components/organisms/VideoOverlay.vue'
import Spinner from '@/components/atoms/Spinner.vue'

const route = useRoute()
const { config } = useConfig()
const report = ref(null)
const isLoading = ref(true)
const filterLoading = ref(false)
const error = ref('')
const exporting = ref(false)

// Total segment count (from initial unfiltered load)
const totalSegmentCount = ref(0)

// Filtering state
const activeTypeFilter = ref('all')
const activeSeverityFilter = ref('all')

// Sorting state
const segmentSort = ref('start_time')
const segmentOrder = ref('asc')

const typeFilters = [
  { key: 'all', label: 'All Types' },
  { key: 'flash', label: 'Flash' },
  { key: 'motion', label: 'Motion' },
]

const severityFilters = [
  { key: 'all', label: 'All Severities' },
  { key: 'high', label: 'High' },
  { key: 'medium', label: 'Medium' },
  { key: 'low', label: 'Low' },
]

// Map frontend sort keys (camelCase) to API sort keys (snake_case)
const sortKeyMap = {
  startTime: 'start_time',
  endTime: 'end_time',
  type: 'type',
  severity: 'severity',
  metricValue: 'metric_value',
}

const videoSrc = ref('')

function buildVideoSrc() {
  if (!report.value) return ''
  const base = import.meta.env.VITE_API_BASE_URL
  const token = getAuthToken()
  return `${base}/videos/${report.value.video.id}/stream?token=${encodeURIComponent(token)}`
}

async function fetchReport() {
  const params = {}
  if (activeTypeFilter.value !== 'all') params.type = activeTypeFilter.value
  if (activeSeverityFilter.value !== 'all') params.severity = activeSeverityFilter.value
  params.segment_sort = segmentSort.value
  params.segment_order = segmentOrder.value

  try {
    const { data } = await api.get(`/videos/${route.params.id}/report`, { params })
    report.value = data
    videoSrc.value = buildVideoSrc()
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to load report'
  }
}

onMounted(async () => {
  await fetchReport()
  if (report.value) {
    totalSegmentCount.value = report.value.segments.length
  }
  isLoading.value = false
})

watch([activeTypeFilter, activeSeverityFilter, segmentSort, segmentOrder], async () => {
  filterLoading.value = true
  await fetchReport()
  // Update total count only when no filters are active (all/all)
  if (activeTypeFilter.value === 'all' && activeSeverityFilter.value === 'all') {
    totalSegmentCount.value = report.value?.segments?.length ?? 0
  }
  filterLoading.value = false
})

function handleSegmentSort(columnKey) {
  const apiKey = sortKeyMap[columnKey] || columnKey
  if (segmentSort.value === apiKey) {
    segmentOrder.value = segmentOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    segmentSort.value = apiKey
    segmentOrder.value = 'asc'
  }
}

async function handleExport(format) {
  exporting.value = true
  try {
    const response = await api.get(`/videos/${route.params.id}/report/${format}`, {
      responseType: 'blob',
    })
    const blob = new Blob([response.data])
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `report_${route.params.id}.${format === 'json' ? 'json' : 'csv'}`
    link.click()
    URL.revokeObjectURL(url)
  } catch {
    error.value = 'Export failed. Please try again.'
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <PageTemplate title="Analysis Report">
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <Spinner size="lg" />
    </div>

    <div v-else-if="error" class="text-error text-center py-12">{{ error }}</div>

    <div v-else-if="report" class="space-y-6 lg:space-y-8">
      <ReportHeader
        :video="report.video"
        :risk-level="report.summary.overallRiskLevel"
      />

      <VideoOverlay
        :video-src="videoSrc"
        :charts="report.charts"
        :duration="report.video.duration"
        :flash-threshold="config?.flashDangerThreshold ?? 3"
        :motion-threshold="config?.motionThreshold ?? 30"
        :luminance-max="config?.luminanceMax ?? 255"
      />

      <StatsPanel :summary="{ ...report.summary, effectiveSamplingRate: report.video.effectiveSamplingRate }" />

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <FlashFrequencyChart
          v-if="report.charts.flashFrequency.length"
          :data="report.charts.flashFrequency"
        />
        <MotionIntensityChart
          v-if="report.charts.motionIntensity.length"
          :data="report.charts.motionIntensity"
        />
      </div>
      <LuminanceChart
        v-if="report.charts.luminance.length"
        :data="report.charts.luminance"
      />

      <SegmentTimeline
        :segments="report.segments"
        :duration="report.video.duration"
      />

      <!-- Segment filter bar -->
      <div class="flex flex-wrap items-center gap-2 sm:gap-3">
        <!-- Type filters -->
        <div class="inline-flex rounded-xl bg-surface-alt p-0.5">
          <button
            v-for="f in typeFilters"
            :key="f.key"
            class="rounded-xl px-3 py-1.5 text-sm font-medium transition-colors"
            :class="activeTypeFilter === f.key
              ? 'bg-surface text-heading shadow-sm border border-line'
              : 'text-muted hover:text-heading border border-transparent'"
            @click="activeTypeFilter = f.key"
          >
            {{ f.label }}
          </button>
        </div>

        <!-- Severity filters -->
        <div class="inline-flex rounded-xl bg-surface-alt p-0.5">
          <button
            v-for="f in severityFilters"
            :key="f.key"
            class="rounded-xl px-3 py-1.5 text-sm font-medium transition-colors"
            :class="activeSeverityFilter === f.key
              ? 'bg-surface text-heading shadow-sm border border-line'
              : 'text-muted hover:text-heading border border-transparent'"
            @click="activeSeverityFilter = f.key"
          >
            {{ f.label }}
          </button>
        </div>

        <!-- Active filter count / loading indicator -->
        <span v-if="filterLoading" class="text-xs text-muted animate-pulse">Updating...</span>
        <span
          v-else-if="report.segments.length !== totalSegmentCount"
          class="text-xs text-muted"
        >
          Showing {{ report.segments.length }} of {{ totalSegmentCount }} segments
        </span>
      </div>

      <SegmentTable
        :segments="report.segments"
        :sort-field="segmentSort"
        :sort-order="segmentOrder"
        @sort="handleSegmentSort"
      />

      <ExportButtons :exporting="exporting" @export="handleExport" />
    </div>
  </PageTemplate>
</template>
