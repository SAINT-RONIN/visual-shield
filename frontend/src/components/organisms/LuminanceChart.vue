<script setup>
// Organism: LuminanceChart visualizes average luminance over time and highlights detected flash moments.
import { computed } from 'vue'
import { buildChartOptions } from '@/utils/chartOptions.js'
import { chartColors } from '@/utils/colors.js'
import BaseLineChart from '@/components/organisms/BaseLineChart.vue'

const props = defineProps({
  data: { type: Array, required: true },
})

const chartData = computed(() => ({
  labels: props.data.map((point) => point.time.toFixed(0) + 's'),
  datasets: [
    {
      label: 'Luminance',
      data: props.data.map((point) => point.luminance),
      borderColor: chartColors.luminance,
      backgroundColor: chartColors.luminanceFill,
      fill: true,
      tension: 0.3,
      pointRadius: props.data.map((point) => (point.flashDetected ? 4 : 0)),
      pointBackgroundColor: props.data.map((point) =>
        point.flashDetected ? chartColors.threshold : 'transparent'
      ),
      pointBorderColor: props.data.map((point) =>
        point.flashDetected ? chartColors.threshold : 'transparent'
      ),
    },
  ],
}))

const chartOptions = buildChartOptions({
  yLabel: 'Brightness',
  y: { max: 255 },
  tooltip: {
    callbacks: {
      afterLabel(context) {
        const idx = context.dataIndex
        return props.data[idx]?.flashDetected ? 'Flash detected' : ''
      },
    },
  },
})
</script>

<template>
  <BaseLineChart
    title="Luminance (Brightness) Over Time"
    :chart-data="chartData"
    :chart-options="chartOptions"
  />
</template>
