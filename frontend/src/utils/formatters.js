/**
 * Format seconds into m:ss display string.
 * @param {number} seconds
 * @param {boolean} showDecimal - if true, show tenths of seconds
 * @returns {string}
 */
export function formatTime(seconds, showDecimal = false) {
  if (!seconds || isNaN(seconds)) return '0:00'
  const m = Math.floor(seconds / 60)
  if (showDecimal) {
    const s = (seconds % 60).toFixed(1)
    return `${m}:${s.padStart(4, '0')}`
  }
  const s = Math.round(seconds % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

/**
 * Format a date string into locale date display.
 * @param {string} dateStr
 * @returns {string}
 */
export function formatDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleDateString()
}

/**
 * Format a date string with abbreviated month (e.g. "Jan 1, 2025").
 * @param {string} dateStr
 * @returns {string}
 */
export function formatDateShort(dateStr) {
  if (!dateStr) return '--'
  return new Date(dateStr).toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

/**
 * Format byte count into human-readable size.
 * @param {number} bytes
 * @returns {string}
 */
export function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}

/**
 * Format seconds into m:ss for video duration display.
 * Returns '--' if no value provided.
 * @param {number} seconds
 * @returns {string}
 */
export function formatDuration(seconds) {
  if (!seconds) return '--'
  const m = Math.floor(seconds / 60)
  const s = Math.round(seconds % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}
