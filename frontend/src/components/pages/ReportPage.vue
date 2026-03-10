<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '@/utils/api.js'
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

const route = useRoute()
const report = ref(null)
const isLoading = ref(true)
const error = ref('')
const exporting = ref(false)

onMounted(async () => {
  try {
    const { data } = await api.get(`/videos/${route.params.id}/report`)
    report.value = data
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to load report'
  } finally {
    isLoading.value = false
  }
})

async function handleExport(format) {
  exporting.value = true
  try {
    const response = await api.get(`/videos/${route.params.id}/export/${format}`, {
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
    <div v-if="isLoading" class="text-body text-center py-12">Loading report...</div>

    <div v-else-if="error" class="text-error text-center py-12">{{ error }}</div>

    <div v-else-if="report" class="space-y-6 lg:space-y-8">
      <ReportHeader
        :video="report.video"
        :risk-level="report.summary.overallRiskLevel"
      />

      <VideoOverlay
        :video-id="report.video.id"
        :charts="report.charts"
        :duration="report.video.duration"
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

      <SegmentTable :segments="report.segments" />

      <ExportButtons :exporting="exporting" @export="handleExport" />
    </div>
  </PageTemplate>
</template>
