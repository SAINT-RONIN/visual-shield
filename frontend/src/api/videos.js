import api, { getAuthToken } from '@/utils/api.js'

/**
 * Fetch a paginated/filtered list of videos.
 * @param {Object} params - Query parameters (status, limit, offset, etc.)
 * @returns {Promise<{ items: Array, total: number }>}
 */
export async function fetchVideos(params = {}) {
  const { data } = await api.get('/videos', { params })
  if (Array.isArray(data)) {
    return { items: data, total: data.length }
  }
  return { items: data.data, total: data.pagination?.total ?? 0 }
}

/**
 * Build the authenticated stream URL for a video.
 * @param {string|number} videoId
 * @returns {string}
 */
export function getVideoStreamUrl(videoId) {
  const base = import.meta.env.VITE_API_BASE_URL
  const token = getAuthToken()
  return `${base}/videos/${videoId}/stream?token=${encodeURIComponent(token)}`
}
