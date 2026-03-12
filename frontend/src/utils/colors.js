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
