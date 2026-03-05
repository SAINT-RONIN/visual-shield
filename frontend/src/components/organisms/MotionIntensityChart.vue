<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
} from 'chart.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip)

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

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
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
      max: 255,
      ticks: { color: '#6b7280' },
      grid: { color: 'rgba(107, 114, 128, 0.15)' },
    },
  },
}
</script>

<template>
  <div class="bg-surface rounded-xl p-4 md:p-6 border border-line">
    <h3 class="text-base md:text-lg font-semibold text-heading mb-4">Motion Intensity</h3>
    <div class="h-64">
      <Line :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
