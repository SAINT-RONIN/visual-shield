<script setup>
// Organism: UploadForm combines file selection, sampling-rate choice, and upload submit controls.
import { ref, reactive } from 'vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'
import ProgressBar from '@/components/atoms/ProgressBar.vue'
import DropZone from '@/components/molecules/DropZone.vue'

defineProps({
  uploading: { type: Boolean, default: false },
  progress: { type: Number, default: 0 },
  error: { type: String, default: '' },
})

const emit = defineEmits(['submit'])

const file = ref(null)
const fileName = ref('')
const samplingRate = ref(15)

const errors = reactive({
  file: '',
})

const rateOptions = [
  { value: 10, label: '10 fps' },
  { value: 15, label: '15 fps (default)' },
  { value: 30, label: '30 fps' },
  { value: 60, label: '60 fps' },
]

const allowedTypes = ['video/mp4', 'video/webm']

function handleFileSelect(selected) {
  file.value = selected
  fileName.value = selected.name
  errors.file = ''
}

function handleUpload() {
  errors.file = ''

  if (!file.value) {
    errors.file = 'Please select a video file'
    return
  }
  if (!allowedTypes.includes(file.value.type)) {
    errors.file = 'Only MP4 and WebM files are supported'
    return
  }

  emit('submit', { file: file.value, samplingRate: samplingRate.value })
}
</script>

<template>
  <div class="bg-surface border border-line rounded-xl p-4 md:p-5 lg:p-6 space-y-6">
    <div>
      <DropZone :file-name="fileName" @select="handleFileSelect" />
      <span v-if="errors.file" class="text-error text-sm mt-2 block">{{ errors.file }}</span>
    </div>
    <AppSelect v-model="samplingRate" label="Sampling Rate (fps)" :options="rateOptions" />
    <ProgressBar v-if="uploading" :value="progress" label="Uploading..." />
    <AlertMessage :message="error" />
    <AppButton :loading="uploading" :disabled="!file" full-width @click="handleUpload">
      {{ uploading ? 'Uploading...' : 'Upload Video' }}
    </AppButton>
  </div>
</template>
