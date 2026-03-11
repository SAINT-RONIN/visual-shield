const axisTitleFont = { size: 11, weight: '500' }
const axisTitleColor = 'rgba(107, 114, 128, 0.75)'

/**
 * Build standard chart options used across all analysis charts.
 * @param {Object} overrides - optional overrides
 * @param {string} [overrides.yLabel] - Y-axis title text
 * @param {Object} [overrides.y] - extra y-axis config
 * @param {Object} [overrides.tooltip] - extra tooltip config
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
        title: {
          display: true,
          text: 'Time (s)',
          font: axisTitleFont,
          color: axisTitleColor,
        },
      },
      y: {
        beginAtZero: true,
        ticks: { color: '#6b7280' },
        grid: { color: 'rgba(107, 114, 128, 0.15)' },
        ...(overrides.yLabel
          ? {
              title: {
                display: true,
                text: overrides.yLabel,
                font: axisTitleFont,
                color: axisTitleColor,
              },
            }
          : {}),
        ...overrides.y,
      },
    },
  }
}
