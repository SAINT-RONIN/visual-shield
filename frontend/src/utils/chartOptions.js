import { chartColors } from '@/utils/colors.js'

const axisTitleFont = { size: 11, weight: '500' }

/**
 * Build standard chart options used across all analysis charts.
 * @param {Object} overrides - optional overrides
 * @param {string} [overrides.yLabel] - Y-axis title text
 * @param {Object} [overrides.y] - extra y-axis config
 * @param {Object} [overrides.tooltip] - extra tooltip config
 * @returns {Object} chart.js options
 */
export function buildChartOptions(overrides = {}) {
  const yAxisTitle = overrides.yLabel
    ? {
        title: {
          display: true,
          text: overrides.yLabel,
          font: axisTitleFont,
          color: chartColors.axisTitle,
        },
      }
    : {}

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
        ticks: { color: chartColors.axis, maxTicksLimit: 10 },
        grid: { color: chartColors.axisGrid },
        title: {
          display: true,
          text: 'Time (s)',
          font: axisTitleFont,
          color: chartColors.axisTitle,
        },
      },
      y: {
        beginAtZero: true,
        ticks: { color: chartColors.axis },
        grid: { color: chartColors.axisGrid },
        ...yAxisTitle,
        ...overrides.y,
      },
    },
  }
}
