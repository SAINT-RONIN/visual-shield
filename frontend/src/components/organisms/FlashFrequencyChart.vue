<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup.js'
import ChartCard from '@/components/atoms/ChartCard.vue'
import { buildChartOptions } from '@/utils/chartOptions.js'

const props = defineProps({
  data: { type: Array, required: true },
  threshold: { type: Number, default: 3 },
})

const chartData = computed(() => ({
  labels: props.data.map((d) => d.time.toFixed(0) + 's'),
  datasets: [
    {
      label: 'Flash Frequency (Hz)',
      data: props.data.map((d) => d.frequency),
      borderColor: '#6366f1',
      backgroundColor: 'rgba(99, 102, 241, 0.15)',
      fill: true,
      tension: 0.3,
      pointRadius: 0,
    },
    {
      label: 'Danger Threshold',
      data: props.data.map(() => props.threshold),
      borderColor: '#ef4444',
      borderDash: [6, 4],
      borderWidth: 1,
      pointRadius: 0,
      fill: false,
    },
  ],
}))

const chartOptions = buildChartOptions()
</script>

<template>
  <ChartCard title="Flash Frequency">
    <Line :data="chartData" :options="chartOptions" />
  </ChartCard>
</template>
