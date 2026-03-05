<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/api.js'
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'
import ProgressBar from '@/components/atoms/ProgressBar.vue'
import DropZone from '@/components/molecules/DropZone.vue'

const router = useRouter()

const file = ref(null)
const fileName = ref('')
const samplingRate = ref(15)
const uploading = ref(false)
const progress = ref(0)
const error = ref('')

const rateOptions = [
  { value: 10, label: '10 fps' },
  { value: 15, label: '15 fps (default)' },
  { value: 30, label: '30 fps' },
  { value: 60, label: '60 fps' },
]

function handleFileSelect(selected) {
  file.value = selected
  fileName.value = selected.name
  error.value = ''
}

async function handleUpload() {
  if (!file.value) {
    error.value = 'Please select a video file'
    return
  }

  error.value = ''
  uploading.value = true
  progress.value = 0

  const formData = new FormData()
  formData.append('video', file.value)
  formData.append('samplingRate', samplingRate.value)

  try {
    await api.post('/videos', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (e) => {
        if (e.total) {
          progress.value = Math.round((e.loaded / e.total) * 100)
        }
      },
    })
    router.push('/dashboard')
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Upload failed'
  } finally {
    uploading.value = false
  }
}
</script>

<template>
  <div class="bg-surface border border-line rounded-xl p-6 space-y-6">
    <DropZone :file-name="fileName" @select="handleFileSelect" />
    <AppSelect v-model="samplingRate" label="Sampling Rate (fps)" :options="rateOptions" />
    <ProgressBar v-if="uploading" :value="progress" label="Uploading..." />
    <AlertMessage :message="error" />
    <AppButton :loading="uploading" full-width @click="handleUpload">
      {{ uploading ? 'Uploading...' : 'Upload Video' }}
    </AppButton>
  </div>
</template>
