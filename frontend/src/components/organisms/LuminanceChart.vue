<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup.js'
import ChartCard from '@/components/atoms/ChartCard.vue'
import { buildChartOptions } from '@/utils/chartOptions.js'

const props = defineProps({
  data: { type: Array, required: true },
})

const chartData = computed(() => ({
  labels: props.data.map((d) => d.time.toFixed(0) + 's'),
  datasets: [
    {
      label: 'Luminance',
      data: props.data.map((d) => d.luminance),
      borderColor: '#6366f1',
      backgroundColor: 'rgba(99, 102, 241, 0.1)',
      fill: true,
      tension: 0.3,
      pointRadius: props.data.map((d) => (d.flashDetected ? 4 : 0)),
      pointBackgroundColor: props.data.map((d) => (d.flashDetected ? '#ef4444' : 'transparent')),
      pointBorderColor: props.data.map((d) => (d.flashDetected ? '#ef4444' : 'transparent')),
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
