<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import api from '@/utils/api.js'
import { fetchVideos as apiFetchVideos } from '@/api/videos.js'
import { useToast } from '@/composables/useToast.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import Spinner from '@/components/atoms/Spinner.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import VideoCard from '@/components/molecules/VideoCard.vue'

const { showToast } = useToast()

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
    const { items, total: totalCount } = await apiFetchVideos(params)
    videos.value = items
    total.value = totalCount
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
    showToast('Video deleted', 'success')
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

    <div v-if="loading" class="flex items-center justify-center py-20">
      <Spinner size="lg" />
    </div>

    <div v-else-if="error" class="text-error text-center py-12">{{ error }}</div>

    <EmptyState
      v-else-if="videos.length === 0 && filterStatus === 'all'"
      title="No videos yet"
      description="Upload your first video to get started."
      action-label="Upload Video"
      action-to="/upload"
    />

    <div v-else-if="videos.length === 0" class="text-center py-12">
      <p class="text-muted text-sm">No videos match the selected filter.</p>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
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
