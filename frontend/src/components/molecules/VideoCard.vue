<script setup>
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import AppButton from '@/components/atoms/AppButton.vue'

defineProps({
  video: { type: Object, required: true },
})

defineEmits(['delete'])

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}

function formatDuration(seconds) {
  if (!seconds) return '--'
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

function formatDate(dateStr) {
  return new Date(dateStr).toLocaleDateString()
}
</script>

<template>
  <div class="bg-surface border border-line rounded-xl p-4 md:p-5 flex flex-col gap-3">
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0">
        <h3 class="text-heading font-medium truncate">{{ video.originalName }}</h3>
        <p class="text-muted text-xs mt-0.5">{{ formatDate(video.createdAt) }}</p>
      </div>
      <StatusBadge :status="video.status" />
    </div>

    <div class="flex gap-4 text-xs text-body">
      <span>{{ formatSize(video.fileSize) }}</span>
      <span>{{ formatDuration(video.duration) }}</span>
      <span>{{ video.effectiveRate || video.samplingRate }} fps</span>
    </div>

    <div v-if="video.status === 'processing'" class="flex items-center gap-2 text-xs text-warning">
      <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      Analyzing video...
    </div>

    <div class="flex gap-2 mt-auto">
      <router-link
        v-if="video.status === 'completed'"
        :to="`/videos/${video.id}/report`"
        class="flex-1"
      >
        <AppButton variant="primary" full-width>View Report</AppButton>
      </router-link>
      <AppButton variant="ghost" @click="$emit('delete', video.id)">Delete</AppButton>
    </div>
  </div>
</template>
