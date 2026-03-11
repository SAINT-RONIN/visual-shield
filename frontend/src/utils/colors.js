export const severityColors = {
  high: '#ef4444',
  medium: '#f59e0b',
  low: '#eab308',
}

export function getSeverityColor(severity) {
  return severityColors[severity] ?? '#6b7280'
}
