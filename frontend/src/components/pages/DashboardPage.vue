<script setup>
// Page: DashboardPage is the route-level view for browsing uploaded videos.
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { fetchVideos as apiFetchVideos, deleteVideo, reanalyzeVideo } from '@/api/videos.js'
import { fetchUsers } from '@/api/admin.js'
import { useAuth } from '@/composables/useAuth.js'
import { useToast } from '@/composables/useToast.js'
import PageTemplate from '@/components/templates/PageTemplate.vue'
import AppButton from '@/components/atoms/AppButton.vue'
import AppSelect from '@/components/atoms/AppSelect.vue'
import Spinner from '@/components/atoms/Spinner.vue'
import EmptyState from '@/components/atoms/EmptyState.vue'
import AlertMessage from '@/components/atoms/AlertMessage.vue'
import VideoCard from '@/components/organisms/VideoCard.vue'

const { user } = useAuth()
const { showToast } = useToast()

const isAdmin = computed(() => user.value?.role === 'admin')

const videos = ref([])
const loading = ref(true)
const filterLoading = ref(false)
const error = ref('')
const filterStatus = ref('all')
const filterUserId = ref('all')
const userOptions = ref([])
let pollInterval = null

// Pagination state
const page = ref(1)
const limit = ref(20)
const total = ref(0)
const totalPages = computed(() => Math.ceil(total.value / limit.value) || 1)

const videoCountLabel = computed(() => {
  const suffix = total.value !== 1 ? 's' : ''
  const scope = isAdmin.value ? ' total' : ''
  return `${total.value} video${suffix}${scope}`
})

const filterOptions = [
  { value: 'all', label: 'All Videos' },
  { value: 'queued', label: 'Queued' },
  { value: 'processing', label: 'Processing' },
  { value: 'completed', label: 'Completed' },
  { value: 'failed', label: 'Failed' },
]

const hasPendingVideos = computed(() =>
  videos.value.some((video) => video.status === 'queued' || video.status === 'processing')
)

async function fetchVideos() {
  const params = {}
  if (filterStatus.value !== 'all') params.status = filterStatus.value
  if (isAdmin.value && filterUserId.value !== 'all') params.userId = filterUserId.value
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
  try {
    const tasks = [fetchVideos()]
    if (isAdmin.value) {
      tasks.push(
        fetchUsers().then(({ users }) => {
          userOptions.value = [
            { value: 'all', label: 'All Users' },
            ...users.map((u) => ({ value: String(u.id), label: u.displayName || u.username })),
          ]
        })
      )
    }
    await Promise.all(tasks)
  } finally {
    loading.value = false
  }
  if (hasPendingVideos.value) startPolling()
})

onUnmounted(() => stopPolling())

watch(filterStatus, async () => {
  page.value = 1
  filterLoading.value = true
  error.value = ''
  try {
    await fetchVideos()
  } finally {
    filterLoading.value = false
  }
  if (hasPendingVideos.value) startPolling()
  else stopPolling()
})

watch(filterUserId, async () => {
  page.value = 1
  filterLoading.value = true
  error.value = ''
  try {
    await fetchVideos()
  } finally {
    filterLoading.value = false
  }
  if (hasPendingVideos.value) startPolling()
  else stopPolling()
})

watch(page, async () => {
  filterLoading.value = true
  error.value = ''
  try {
    await fetchVideos()
  } finally {
    filterLoading.value = false
  }
  if (hasPendingVideos.value) startPolling()
  else stopPolling()
})

async function handleDelete(id) {
  try {
    await deleteVideo(id)
    videos.value = videos.value.filter((video) => video.id !== id)
    showToast('Video deleted', 'success')
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to delete video'
  }
}

async function handleReanalyze(id) {
  const video = videos.value.find((video) => video.id === id)
  try {
    const updated = await reanalyzeVideo(id, video?.samplingRate || 15)
    const videoIndex = videos.value.findIndex((video) => video.id === id)
    if (videoIndex !== -1) videos.value[videoIndex] = updated
    startPolling()
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Failed to queue re-analysis'
  }
}
</script>

<template>
  <PageTemplate :title="isAdmin ? 'All Videos' : 'Your Videos'" max-width="max-w-none">
    <div class="flex items-center justify-between mb-6 -mt-2 gap-4 flex-wrap">
      <div class="flex items-center gap-3 flex-wrap">
        <p class="text-body text-sm">{{ videoCountLabel }}</p>
        <AppSelect
          v-if="isAdmin && userOptions.length > 1"
          v-model="filterUserId"
          :options="userOptions"
        />
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

    <AlertMessage v-else-if="error" type="error" :message="error" />

    <EmptyState
      v-else-if="videos.length === 0 && filterStatus === 'all' && !isAdmin"
      title="No videos yet"
      description="Upload your first video to get started."
      action-label="Upload Video"
      action-to="/upload"
    />

    <div v-else-if="videos.length === 0 && filterStatus === 'all' && isAdmin" class="text-center py-12">
      <p class="text-muted text-sm">No videos have been uploaded yet.</p>
    </div>

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
