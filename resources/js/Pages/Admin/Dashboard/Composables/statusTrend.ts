import type { InstitutionStatusHistoryPoint } from '../types';

export interface StatusTrendSummary {
  direction: 'down' | 'up' | 'flat';
  from: number;
  to: number;
}

/** First vs last day of overdue institutions; null when there is no history to compare. */
export function summarizeStatusTrend(points: readonly InstitutionStatusHistoryPoint[]): StatusTrendSummary | null {
  if (points.length === 0) {
    return null;
  }

  const from = points[0].overdue;
  const to = points[points.length - 1].overdue;

  return { direction: to < from ? 'down' : to > from ? 'up' : 'flat', from, to };
}
