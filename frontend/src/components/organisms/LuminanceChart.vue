<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup.js'
import ChartCard from '@/components/atoms/ChartCard.vue'
import { buildChartOptions } from '@/utils/chartOptions.js'
import { chartColors } from '@/utils/colors.js'

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
      pointBackgroundColor: props.data.map((point) => (point.flashDetected ? chartColors.threshold : 'transparent')),
      pointBorderColor: props.data.map((point) => (point.flashDetected ? chartColors.threshold : 'transparent')),
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
  <ChartCard title="Luminance (Brightness) Over Time">
    <Line :data="chartData" :options="chartOptions" />
  </ChartCard>
</template>
