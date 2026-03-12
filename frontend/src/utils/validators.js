/**
 * Validate a video file against a maximum byte size.
 * @param {File} file
 * @param {number} maxBytes
 * @returns {string|null} Error message string, or null if valid.
 */
export function validateVideoFile(file, maxBytes) {
  if (file.size > maxBytes) {
    const maxMB = Math.round(maxBytes / 1048576)
    const fileMB = Math.round(file.size / 1048576)
    return `File is too large (${fileMB} MB). Maximum allowed size is ${maxMB} MB.`
  }
  return null
}
