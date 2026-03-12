/**
 * Maps frontend camelCase column keys to API snake_case sort keys.
 * Used in ReportPage (for building query params) and SegmentTable (for sort indicator matching).
 */
export const segmentSortKeyMap = {
  startTime: 'start_time',
  endTime: 'end_time',
  type: 'type',
  severity: 'severity',
  metricValue: 'metric_value',
}
