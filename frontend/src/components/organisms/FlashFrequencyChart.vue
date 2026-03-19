<script setup>
import { computed } from 'vue'
import { buildChartOptions } from '@/utils/chartOptions.js'
import { chartColors } from '@/utils/colors.js'
import BaseLineChart from '@/components/organisms/BaseLineChart.vue'

const props = defineProps({
  data: { type: Array, required: true },
  threshold: { type: Number, default: 3 },
})

const chartData = computed(() => ({
  labels: props.data.map((point) => point.time.toFixed(0) + 's'),
  datasets: [
    {
      label: 'Flash Frequency (Hz)',
      data: props.data.map((point) => point.frequency),
      borderColor: chartColors.flash,
      backgroundColor: chartColors.flashFill,
      fill: true,
      tension: 0.3,
      pointRadius: 0,
    },
    {
      label: 'Danger Threshold',
      data: props.data.map(() => props.threshold),
      borderColor: chartColors.threshold,
      borderDash: [6, 4],
      borderWidth: 1,
      pointRadius: 0,
      fill: false,
    },
  ],
}))

const chartOptions = buildChartOptions({ yLabel: 'Hz' })
</script>

<template>
  <BaseLineChart
    title="Flash Frequency Over Time"
    :chart-data="chartData"
    :chart-options="chartOptions"
  />
</template>
