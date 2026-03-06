<script setup>
import { ref } from 'vue'
import StatusBadge from '@/components/atoms/StatusBadge.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import SeverityBadge from '@/components/atoms/SeverityBadge.vue'
import ProgressBar from '@/components/atoms/ProgressBar.vue'

const props = defineProps({
  video: { type: Object, required: true },
})

const emit = defineEmits(['delete', 'reanalyze'])

const confirmingDelete = ref(false)

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
    <!-- Header row -->
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0">
        <h3 class="text-heading font-medium truncate">{{ video.originalName }}</h3>
        <p class="text-muted text-xs mt-0.5">{{ formatDate(video.createdAt) }}</p>
      </div>
      <div class="flex items-center gap-1.5 shrink-0">
        <SeverityBadge v-if="video.riskLevel" :severity="video.riskLevel" />
        <StatusBadge :status="video.status" />
      </div>
    </div>

    <!-- Meta row -->
    <div class="flex gap-4 text-xs text-body">
      <span>{{ formatSize(video.fileSize) }}</span>
      <span>{{ formatDuration(video.duration) }}</span>
      <span>{{ video.effectiveRate || video.samplingRate }} fps</span>
    </div>

    <!-- Progress bar (processing) -->
    <div v-if="video.status === 'processing'">
      <ProgressBar :value="video.progress || 0" :label="video.progressMessage || 'Analyzing video...'" />
    </div>

    <!-- Error message (failed) -->
    <div v-if="video.status === 'failed' && video.errorMessage"
         class="text-xs text-error bg-error/10 rounded-lg px-3 py-2 break-words">
      {{ video.errorMessage }}
    </div>

    <!-- Confirm delete -->
    <div v-if="confirmingDelete" class="text-xs text-body bg-surface-alt rounded-lg px-3 py-2">
      <p class="mb-2">Delete this video? This cannot be undone.</p>
      <div class="flex gap-2">
        <AppButton variant="danger" size="sm" @click="emit('delete', video.id); confirmingDelete = false">
          Yes, delete
        </AppButton>
        <AppButton variant="ghost" size="sm" @click="confirmingDelete = false">Cancel</AppButton>
      </div>
    </div>

    <!-- Action buttons -->
    <div v-if="!confirmingDelete" class="flex gap-2 mt-auto flex-wrap">
      <router-link v-if="video.status === 'completed'" :to="`/videos/${video.id}/report`" class="flex-1">
        <AppButton variant="primary" full-width>View Report</AppButton>
      </router-link>
      <AppButton
        v-if="video.status === 'completed' || video.status === 'failed'"
        variant="secondary"
        @click="emit('reanalyze', video.id)"
      >
        Re-analyze
      </AppButton>
      <AppButton variant="ghost" @click="confirmingDelete = true">Delete</AppButton>
    </div>
  </div>
</template>
