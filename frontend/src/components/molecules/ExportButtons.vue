<script setup>
import { ref } from 'vue'
import api from '@/utils/api.js'
import AppButton from '@/components/atoms/AppButton.vue'

const props = defineProps({
  videoId: { type: Number, required: true },
})

const exporting = ref(false)

async function downloadExport(format) {
  exporting.value = true
  try {
    const response = await api.get(`/videos/${props.videoId}/export/${format}`, {
      responseType: 'blob',
    })
    const blob = new Blob([response.data])
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `report_${props.videoId}.${format === 'json' ? 'json' : 'csv'}`
    link.click()
    URL.revokeObjectURL(url)
  } catch {
    // silently fail - user can retry
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <div class="flex justify-end gap-4">
    <AppButton variant="secondary" :disabled="exporting" @click="downloadExport('json')">
      Download JSON
    </AppButton>
    <AppButton variant="secondary" :disabled="exporting" @click="downloadExport('csv')">
      Download CSV
    </AppButton>
  </div>
</template>
