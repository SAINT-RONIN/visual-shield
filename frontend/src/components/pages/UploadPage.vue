<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/api.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import UploadForm from '@/components/organisms/UploadForm.vue'

const router = useRouter()

const uploading = ref(false)
const progress = ref(0)
const error = ref('')

async function handleSubmit({ file, samplingRate }) {
  uploading.value = true
  progress.value = 0
  error.value = ''

  const formData = new FormData()
  formData.append('video', file)
  formData.append('samplingRate', samplingRate)

  try {
    await api.post('/videos', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress(e) {
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
  <PageTemplate title="Upload Video" max-width="max-w-2xl">
    <UploadForm
      :uploading="uploading"
      :progress="progress"
      :error="error"
      @submit="handleSubmit"
    />
  </PageTemplate>
</template>
