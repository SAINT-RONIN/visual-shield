export const severityColors = {
  high: '#ef4444',
  medium: '#f59e0b',
  low: '#eab308',
}

export function getSeverityColor(severity) {
  return severityColors[severity] ?? '#6b7280'
}

/** Metric accent colors used in stat cards and equalizer segments */
export const metricColors = {
  flash: '#ef4444',
  motion: '#f59e0b',
  sampling: '#22c55e',
}

/** Equalizer segment colors at each threshold zone (lit state) */
export const eqZoneColors = {
  safe: '#22c55e',
  warning: '#fbbf24',
  danger: '#ef4444',
}

/** Equalizer segment dim colors at each threshold zone (unlit state) */
export const eqZoneDimColors = {
  safe: 'rgba(34, 197, 94, 0.1)',
  warning: 'rgba(251, 191, 36, 0.1)',
  danger: 'rgba(239, 68, 68, 0.1)',
}

/** Colors used in the VideoOverlay canvas graph lines */
export const overlayGraphColors = {
  flash: '#818cf8',
  motion: '#fbbf24',
  luminance: '#22d3ee',
}

/** Colors used in the VideoOverlay canvas grid and CSS custom properties */
export const overlayColors = {
  gridLine: 'rgba(255, 255, 255, 0.55)',
  gridLabel: 'rgba(255, 255, 255, 0.7)',
  videoBg: '#000000',
  progressBar: '#e11d48',
  progressGlow: 'rgba(225, 29, 72, 0.5)',
  controlText: '#fff',
  controlTextDim: 'rgba(255, 255, 255, 0.8)',
  controlBg: 'rgba(255, 255, 255, 0.2)',
  controlBgSubtle: 'rgba(255, 255, 255, 0.1)',
  controlBarGradient: 'linear-gradient(transparent, rgba(0, 0, 0, 0.7) 40%, rgba(0, 0, 0, 0.85))',
  thumbBg: '#fff',
}

/** Colors used across analysis charts */
export const chartColors = {
  flash: '#6366f1',
  flashFill: 'rgba(99, 102, 241, 0.15)',
  motion: '#f59e0b',
  motionFill: 'rgba(245, 158, 11, 0.08)',
  luminance: '#6366f1',
  luminanceFill: 'rgba(99, 102, 241, 0.1)',
  threshold: '#ef4444',
  axis: '#6b7280',
  axisTitle: 'rgba(107, 114, 128, 0.75)',
  axisGrid: 'rgba(107, 114, 128, 0.15)',
}
