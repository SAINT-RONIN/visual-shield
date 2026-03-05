<script setup>
import { ref } from 'vue'
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

const rateOptions = [
  { value: 10, label: '10 fps' },
  { value: 15, label: '15 fps (default)' },
  { value: 30, label: '30 fps' },
  { value: 60, label: '60 fps' },
]

function handleFileSelect(selected) {
  file.value = selected
  fileName.value = selected.name
}

function handleUpload() {
  if (!file.value) return
  emit('submit', { file: file.value, samplingRate: samplingRate.value })
}
</script>

<template>
  <div class="bg-surface border border-line rounded-xl p-4 md:p-5 lg:p-6 space-y-6">
    <DropZone :file-name="fileName" @select="handleFileSelect" />
    <AppSelect v-model="samplingRate" label="Sampling Rate (fps)" :options="rateOptions" />
    <ProgressBar v-if="uploading" :value="progress" label="Uploading..." />
    <AlertMessage :message="error" />
    <AppButton :loading="uploading" :disabled="!file" full-width @click="handleUpload">
      {{ uploading ? 'Uploading...' : 'Upload Video' }}
    </AppButton>
  </div>
</template>
