import api, { apiBaseUrl, getAuthToken } from '@/utils/api.js'

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
 *
 * NOTE: The auth token is intentionally passed as a query parameter here.
 * The HTML <video> element does not support setting custom headers (e.g. Authorization),
 * so the token must be embedded in the URL. The backend accepts query-param token auth
 * for streaming endpoints only. This is an accepted exception to the header-auth rule.
 */
export function getVideoStreamUrl(videoId) {
  const token = getAuthToken()
  return `${apiBaseUrl}/videos/${videoId}/stream?token=${encodeURIComponent(token)}`
}

/**
 * Delete a video by ID.
 * @param {string|number} id
 */
export async function deleteVideo(id) {
  await api.delete(`/videos/${id}`)
}

/**
 * Queue a video for re-analysis.
 * @param {string|number} id
 * @param {number} samplingRate
 * @returns {Promise<Object>} updated video object
 */
export async function reanalyzeVideo(id, samplingRate) {
  const { data } = await api.put(`/videos/${id}/reanalyze`, { samplingRate })
  return data.data
}

/**
 * Upload a new video.
 * @param {FormData} formData
 * @param {Function} onProgress - Axios onUploadProgress callback
 */
export async function uploadVideo(formData, onProgress) {
  const { data } = await api.post('/videos', formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
    onUploadProgress: onProgress,
  })
  return data.data
}

/**
 * Fetch the analysis report for a video.
 * @param {string|number} videoId
 * @param {Object} params - Query parameters (type, severity, segment_sort, segment_order)
 * @returns {Promise<Object>} report data
 */
export async function fetchReport(videoId, params) {
  const { data } = await api.get(`/videos/${videoId}/report`, { params })
  return data
}

/**
 * Export a report in the specified format.
 * @param {string|number} videoId
 * @param {string} format - 'json' or 'csv'
 * @returns {Promise<Blob>}
 */
export async function exportReport(videoId, format) {
  const response = await api.get(`/videos/${videoId}/report/${format}`, { responseType: 'blob' })
  return response.data
}
