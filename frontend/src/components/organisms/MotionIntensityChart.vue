<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup.js'
import ChartCard from '@/components/atoms/ChartCard.vue'
import { buildChartOptions } from '@/utils/chartOptions.js'

const props = defineProps({
  data: { type: Array, required: true },
  threshold: { type: Number, default: 30 },
})

const chartData = computed(() => ({
  labels: props.data.map((d) => d.time.toFixed(0) + 's'),
  datasets: [
    {
      label: 'Motion Intensity',
      data: props.data.map((d) => d.intensity),
      borderColor: '#f59e0b',
      backgroundColor: 'rgba(245, 158, 11, 0.08)',
      tension: 0.3,
      pointRadius: 0,
    },
    {
      label: 'Threshold',
      data: props.data.map(() => props.threshold),
      borderColor: '#ef4444',
      borderDash: [6, 4],
      borderWidth: 1,
      pointRadius: 0,
    },
  ],
}))

const chartOptions = buildChartOptions({ y: { max: 255 } })
</script>

<template>
  <ChartCard title="Motion Intensity">
    <Line :data="chartData" :options="chartOptions" />
  </ChartCard>
</template>
