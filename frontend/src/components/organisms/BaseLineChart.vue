<script setup>
import { Line } from 'vue-chartjs'
import '@/utils/chartSetup.js'
import ChartCard from '@/components/atoms/ChartCard.vue'

defineProps({
  title: { type: String, required: true },
  chartData: { type: Object, required: true },
  chartOptions: { type: Object, required: true },
})
</script>

<template>
  <ChartCard :title="title">
    <Line :data="chartData" :options="chartOptions" />
  </ChartCard>
</template>
