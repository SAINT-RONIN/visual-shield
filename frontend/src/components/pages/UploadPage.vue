<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { uploadVideo } from '@/api/videos.js'
import { useConfig } from '@/composables/useConfig.js'
import { useToast } from '@/composables/useToast.js'
import { validateVideoFile } from '@/utils/validators.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import UploadForm from '@/components/organisms/UploadForm.vue'

const router = useRouter()
const { config } = useConfig()
const { showToast } = useToast()

const uploading = ref(false)
const progress = ref(0)
const error = ref('')

async function handleSubmit({ file, samplingRate }) {
  uploading.value = true
  progress.value = 0
  error.value = ''

  const maxSize = config.value?.maxUploadSize ?? 500 * 1024 * 1024
  const validationError = validateVideoFile(file, maxSize)
  if (validationError) {
    error.value = validationError
    uploading.value = false
    return
  }

  const formData = new FormData()
  formData.append('video', file)
  formData.append('samplingRate', samplingRate)

  try {
    await uploadVideo(formData, (e) => {
      if (e.total) {
        progress.value = Math.round((e.loaded / e.total) * 100)
      }
    })
    showToast('Video uploaded successfully', 'success')
    router.push('/dashboard')
  } catch (err) {
    if (err.response?.data?.error?.message) {
      error.value = err.response.data.error.message
    } else if (err.response?.status === 413) {
      error.value = 'File is too large for the server to accept. Please use a smaller file.'
    } else if (!err.response) {
      error.value = 'Could not reach the server. Is the backend running?'
    } else {
      error.value = `Upload failed (HTTP ${err.response.status})`
    }
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
