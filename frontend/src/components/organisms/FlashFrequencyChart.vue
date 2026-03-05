<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup.js'

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

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      mode: 'index',
      intersect: false,
    },
  },
  scales: {
    x: {
      ticks: { color: '#6b7280', maxTicksLimit: 10 },
      grid: { color: 'rgba(107, 114, 128, 0.15)' },
    },
    y: {
      beginAtZero: true,
      ticks: { color: '#6b7280' },
      grid: { color: 'rgba(107, 114, 128, 0.15)' },
    },
  },
}
</script>

<template>
  <div class="bg-surface rounded-xl p-4 md:p-6 border border-line">
    <h3 class="text-base md:text-lg font-semibold text-heading mb-4">Flash Frequency</h3>
    <div class="h-64">
      <Line :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
