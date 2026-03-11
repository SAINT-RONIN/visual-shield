<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import api from '@/utils/api.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import VideoCard from '@/components/molecules/VideoCard.vue'

const videos = ref([])
const loading = ref(true)
const filterLoading = ref(false)
const error = ref('')
const filterStatus = ref('all')
let pollInterval = null

// Pagination state
const page = ref(1)
const limit = ref(20)
const total = ref(0)
const totalPages = computed(() => Math.ceil(total.value / limit.value) || 1)

const filterOptions = [
  { value: 'all', label: 'All Videos' },
  { value: 'queued', label: 'Queued' },
  { value: 'processing', label: 'Processing' },
  { value: 'completed', label: 'Completed' },
  { value: 'failed', label: 'Failed' },
]

const hasPendingVideos = computed(() =>
  videos.value.some((v) => v.status === 'queued' || v.status === 'processing')
)

async function fetchVideos() {
  const params = {}
  if (filterStatus.value !== 'all') params.status = filterStatus.value
  params.limit = limit.value
  params.offset = (page.value - 1) * limit.value

  try {
    const { data } = await api.get('/videos', { params })
    // Support both paginated { data, pagination } and legacy array responses
    if (Array.isArray(data)) {
      videos.value = data
      total.value = data.length
    } else {
      videos.value = data.data
      total.value = data.pagination?.total ?? 0
    }
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to load videos'
  }
}

function startPolling() {
  stopPolling()
  pollInterval = setInterval(async () => {
    await fetchVideos()
    if (!hasPendingVideos.value) stopPolling()
  }, 5000)
}

function stopPolling() {
  if (pollInterval) { clearInterval(pollInterval); pollInterval = null }
}

onMounted(async () => {
  await fetchVideos()
  loading.value = false
  if (hasPendingVideos.value) startPolling()
})

onUnmounted(() => stopPolling())

watch(filterStatus, async () => {
  page.value = 1
  filterLoading.value = true
  error.value = ''
  await fetchVideos()
  filterLoading.value = false
  if (hasPendingVideos.value) startPolling()
  else stopPolling()
})

watch(page, async () => {
  filterLoading.value = true
  error.value = ''
  await fetchVideos()
  filterLoading.value = false
  if (hasPendingVideos.value) startPolling()
  else stopPolling()
})

async function handleDelete(id) {
  try {
    await api.delete(`/videos/${id}`)
    videos.value = videos.value.filter((v) => v.id !== id)
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to delete video'
  }
}

async function handleReanalyze(id) {
  const video = videos.value.find((v) => v.id === id)
  try {
    const { data } = await api.put(`/videos/${id}/reanalyze`, {
      samplingRate: video?.samplingRate || 15,
    })
    const idx = videos.value.findIndex((v) => v.id === id)
    if (idx !== -1) videos.value[idx] = data
    startPolling()
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to queue re-analysis'
  }
}
</script>

<template>
  <PageTemplate title="Your Videos" max-width="max-w-none">
    <div class="flex items-center justify-between mb-6 -mt-2 gap-4 flex-wrap">
      <div class="flex items-center gap-3">
        <p class="text-body text-sm">{{ total }} video{{ total !== 1 ? 's' : '' }}</p>
        <AppSelect v-if="videos.length > 0 || filterStatus !== 'all'" v-model="filterStatus" :options="filterOptions" />
        <span v-if="filterLoading" class="text-muted text-xs animate-pulse">Updating...</span>
      </div>
      <router-link to="/upload">
        <AppButton>Upload Video</AppButton>
      </router-link>
    </div>

    <div v-if="loading" class="text-body text-center py-12">Loading videos...</div>

    <div v-else-if="error" class="text-error text-center py-12">{{ error }}</div>

    <div v-else-if="videos.length === 0 && filterStatus === 'all'" class="text-center py-16">
      <svg class="w-16 h-16 mx-auto mb-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
      </svg>
      <p class="text-body mb-2">No videos yet</p>
      <p class="text-muted text-sm mb-4">Upload a video to get started with accessibility analysis</p>
      <router-link to="/upload">
        <AppButton>Upload Your First Video</AppButton>
      </router-link>
    </div>

    <div v-else-if="videos.length === 0" class="text-center py-12">
      <p class="text-muted text-sm">No videos match the selected filter.</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 md:gap-5 lg:gap-6">
      <VideoCard
        v-for="video in videos"
        :key="video.id"
        :video="video"
        @delete="handleDelete"
        @reanalyze="handleReanalyze"
      />
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex flex-col sm:flex-row items-center justify-center gap-3 mt-6">
      <AppButton
        variant="secondary"
        size="sm"
        :disabled="page <= 1"
        @click="page--"
      >
        Previous
      </AppButton>
      <span class="text-body text-sm">Page {{ page }} of {{ totalPages }}</span>
      <AppButton
        variant="secondary"
        size="sm"
        :disabled="page >= totalPages"
        @click="page++"
      >
        Next
      </AppButton>
    </div>
  </PageTemplate>
</template>
