export interface MetricPoint {
  /** `YYYY-MM`. */
  month: string;
  /** Percent; null for a month with nothing to measure. */
  value: number | null;
}

export interface MetricTrendSummary {
  first: MetricPoint & { value: number };
  last: MetricPoint & { value: number };
  /** Latest against the first measured month, so a metric that is rising or sliding reads without the plot. */
  direction: 'up' | 'down' | 'flat';
}

/** Null when fewer than two months were measured, so there is no trend to describe. */
export function summarizeMetricTrend(points: MetricPoint[]): MetricTrendSummary | null {
  const measured = points.filter((point): point is MetricPoint & { value: number } => point.value !== null);

  if (measured.length < 2) {
    return null;
  }

  const first = measured[0];
  const last = measured[measured.length - 1];

  return {
    first,
    last,
    direction: last.value > first.value ? 'up' : last.value < first.value ? 'down' : 'flat',
  };
}
