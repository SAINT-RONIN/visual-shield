<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import api from '@/utils/api.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import VideoCard from '@/components/molecules/VideoCard.vue'

const videos = ref([])
const loading = ref(true)
const error = ref('')
let pollInterval = null

const hasPendingVideos = computed(() =>
  videos.value.some((v) => v.status === 'queued' || v.status === 'processing')
)

async function fetchVideos() {
  try {
    const { data } = await api.get('/videos')
    videos.value = data
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to load videos'
  }
}

function startPolling() {
  stopPolling()
  pollInterval = setInterval(async () => {
    await fetchVideos()
    if (!hasPendingVideos.value) {
      stopPolling()
    }
  }, 5000)
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

onMounted(async () => {
  await fetchVideos()
  loading.value = false

  if (hasPendingVideos.value) {
    startPolling()
  }
})

onUnmounted(() => {
  stopPolling()
})

async function handleDelete(id) {
  try {
    await api.delete(`/videos/${id}`)
    videos.value = videos.value.filter((v) => v.id !== id)
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to delete video'
  }
}
</script>

<template>
  <PageTemplate title="Your Videos">
    <div class="flex items-center justify-between mb-6 -mt-2">
      <p class="text-body text-sm">{{ videos.length }} video{{ videos.length !== 1 ? 's' : '' }}</p>
      <router-link to="/upload">
        <AppButton>Upload Video</AppButton>
      </router-link>
    </div>

    <div v-if="loading" class="text-body text-center py-12">Loading videos...</div>

    <div v-else-if="error" class="text-error text-center py-12">{{ error }}</div>

    <div v-else-if="videos.length === 0" class="text-center py-16">
      <svg class="w-16 h-16 mx-auto mb-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
      </svg>
      <p class="text-body mb-2">No videos yet</p>
      <p class="text-muted text-sm">Upload a video to get started with accessibility analysis</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-5 lg:gap-6">
      <VideoCard
        v-for="video in videos"
        :key="video.id"
        :video="video"
        @delete="handleDelete"
      />
    </div>
  </PageTemplate>
</template>
