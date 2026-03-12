<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup.js'
import ChartCard from '@/components/atoms/ChartCard.vue'
import { buildChartOptions } from '@/utils/chartOptions.js'
import { chartColors } from '@/utils/colors.js'

const props = defineProps({
  data: { type: Array, required: true },
  threshold: { type: Number, default: 30 },
})

const chartData = computed(() => ({
  labels: props.data.map((point) => point.time.toFixed(0) + 's'),
  datasets: [
    {
      label: 'Motion Intensity',
      data: props.data.map((point) => point.intensity),
      borderColor: chartColors.motion,
      backgroundColor: chartColors.motionFill,
      tension: 0.3,
      pointRadius: 0,
    },
    {
      label: 'Threshold',
      data: props.data.map(() => props.threshold),
      borderColor: chartColors.threshold,
      borderDash: [6, 4],
      borderWidth: 1,
      pointRadius: 0,
    },
  ],
}))

const chartOptions = buildChartOptions({ yLabel: 'Intensity', y: { max: 255 } })
</script>

<template>
  <ChartCard title="Motion Intensity Over Time">
    <Line :data="chartData" :options="chartOptions" />
  </ChartCard>
</template>
