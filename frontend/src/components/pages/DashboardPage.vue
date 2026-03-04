<script setup>
import { ref, onMounted } from 'vue'
import api from '@/utils/api.js'

const healthStatus = ref(null)
const error = ref(null)

onMounted(async () => {
  try {
    const response = await api.get('/health')
    healthStatus.value = response.data
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to connect to backend'
  }
})
</script>

<template>
  <div class="min-h-screen bg-gray-950 text-white flex items-center justify-center">
    <div class="bg-gray-900 rounded-xl p-8 border border-gray-800">
      <h1 class="text-2xl font-bold mb-4">Visual Shield - Dashboard</h1>
      <div v-if="healthStatus" class="text-green-400">
        Backend connected: {{ JSON.stringify(healthStatus) }}
      </div>
      <div v-else-if="error" class="text-red-400">
        Error: {{ error }}
      </div>
      <div v-else class="text-gray-400">
        Connecting to backend...
      </div>
    </div>
  </div>
</template>
