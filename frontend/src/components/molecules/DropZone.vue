<script setup>
// Molecule: DropZone combines file selection and drag-and-drop feedback into one upload helper.
import { ref } from 'vue'

defineProps({
  fileName: { type: String, default: '' },
  accept: { type: String, default: 'video/mp4,video/webm' },
  hint: { type: String, default: 'MP4 or WebM, max 500 MB' },
})

const emit = defineEmits(['select'])
const dragging = ref(false)

function handleFileSelect(e) {
  const file = e.target.files[0]
  if (file) emit('select', file)
}

function handleDrop(e) {
  dragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) emit('select', file)
}
</script>

<template>
  <label
    @dragover.prevent="dragging = true"
    @dragleave="dragging = false"
    @drop.prevent="handleDrop"
    class="border-2 border-dashed rounded-xl p-10 text-center cursor-pointer transition-colors focus-within:outline-none focus-within:ring-2 focus-within:ring-primary focus-within:border-primary"
    :class="dragging ? 'border-primary bg-primary/10' : 'border-line-strong hover:border-body'"
  >
    <input
      type="file"
      :accept="accept"
      class="hidden"
      @change="handleFileSelect"
    />
    <svg class="w-12 h-12 mx-auto mb-3 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
    </svg>
    <p v-if="fileName" class="text-heading font-medium">{{ fileName }}</p>
    <p v-else class="text-body">
      Drag & drop a video file or <span class="text-link">browse</span>
    </p>
    <p class="text-muted text-xs mt-1">{{ hint }}</p>
  </label>
</template>
