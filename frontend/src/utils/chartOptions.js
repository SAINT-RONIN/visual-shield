/**
 * Build standard chart options used across all analysis charts.
 * @param {Object} overrides - optional overrides for the y-axis config
 * @returns {Object} chart.js options
 */
export function buildChartOptions(overrides = {}) {
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        mode: 'index',
        intersect: false,
        ...overrides.tooltip,
      },
    },
    scales: {
      x: {
        ticks: { color: '#6b7280', maxTicksLimit: 10 },
        grid: { color: 'rgba(107, 114, 128, 0.15)' },
      },
      y: {
        beginAtZero: true,
        ticks: { color: '#6b7280' },
        grid: { color: 'rgba(107, 114, 128, 0.15)' },
        ...overrides.y,
      },
    },
  }
}
