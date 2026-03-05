<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup.js'

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

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        afterLabel(context) {
          const idx = context.dataIndex
          return props.data[idx]?.flashDetected ? 'Flash detected' : ''
        },
      },
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
    <h3 class="text-base md:text-lg font-semibold text-heading mb-4">Luminance (Brightness)</h3>
    <div class="h-64">
      <Line :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>
