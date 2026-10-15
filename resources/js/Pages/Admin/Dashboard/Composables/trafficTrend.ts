import type { AnalyticsOverviewData } from '@/Types/api.d';

export interface TrafficTrendSummary {
  peakDate: string;
  peakViews: number;
  /** Second half of the period against the first, so a rising or fading site reads without the plot. */
  direction: 'up' | 'down' | 'flat';
}

/** Null when the series is too short to say anything about a trend. */
export function summarizeTrafficTrend(series: AnalyticsOverviewData['series']): TrafficTrendSummary | null {
  if (series.length < 2) {
    return null;
  }

  const peak = series.reduce((best, point) => (point.pageviews > best.pageviews ? point : best));
  const middle = Math.floor(series.length / 2);
  const sum = (points: AnalyticsOverviewData['series']) => points.reduce((total, point) => total + point.pageviews, 0);
  const first = sum(series.slice(0, middle));
  const second = sum(series.slice(middle));

  return {
    // Umami returns 'YYYY-MM-DD HH:mm:ss'; the day is all the sentence needs.
    peakDate: peak.date.slice(0, 10),
    peakViews: peak.pageviews,
    direction: second > first ? 'up' : second < first ? 'down' : 'flat',
  };
}
