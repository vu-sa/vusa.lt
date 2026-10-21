export type RepMetricStatus = 'no_data' | 'improved' | 'behind' | 'measured';

interface StatusInput {
  now: number | null;
  baseline: number | null;
}

// Higher is better for every rep metric. Colour is never the only signal: each status has a word.
export function repMetricStatus(metric: StatusInput): RepMetricStatus {
  if (metric.now === null) {
    return 'no_data';
  }

  if (metric.baseline === null || metric.now === metric.baseline) {
    return 'measured';
  }

  return metric.now > metric.baseline ? 'improved' : 'behind';
}

export function repMetricStatusClass(metric: StatusInput): string {
  return {
    no_data: 'text-status-neutral',
    measured: 'text-status-neutral',
    improved: 'text-status-progress',
    behind: 'text-status-attention',
  }[repMetricStatus(metric)];
}

export type RepMetricTrendDirection = 'up' | 'down' | 'flat';

export interface RepMetricTrend {
  direction: RepMetricTrendDirection;
  /** Percentage points since the reference day, signed. */
  delta: number;
}

// Values carry one decimal, so a change under half a point is noise rather than movement.
const FLAT_BELOW = 0.5;

export function repMetricTrend(metric: { now: number | null; previous?: number | null }): RepMetricTrend | null {
  if (metric.now === null || metric.previous === null || metric.previous === undefined) {
    return null;
  }

  const delta = Math.round((metric.now - metric.previous) * 10) / 10;

  return { direction: Math.abs(delta) < FLAT_BELOW ? 'flat' : delta > 0 ? 'up' : 'down', delta };
}

export function repMetricTrendClass(direction: RepMetricTrendDirection): string {
  return { up: 'text-status-progress', down: 'text-status-attention', flat: 'text-status-neutral' }[direction];
}
