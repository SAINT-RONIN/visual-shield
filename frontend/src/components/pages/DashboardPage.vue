<script setup>
import { ref, onMounted } from 'vue'
import api from '@/utils/api.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'

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
  <PageTemplate title="Dashboard">
    <div class="bg-surface rounded-xl p-6 border border-line">
      <div v-if="healthStatus" class="text-success">
        Backend connected: {{ JSON.stringify(healthStatus) }}
      </div>
      <div v-else-if="error" class="text-error">
        Error: {{ error }}
      </div>
      <div v-else class="text-body">
        Connecting to backend...
      </div>
    </div>
  </PageTemplate>
</template>
