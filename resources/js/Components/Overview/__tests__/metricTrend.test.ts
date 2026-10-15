import { describe, expect, it } from 'vitest';

import { summarizeMetricTrend } from '../metricTrend';

describe('summarizeMetricTrend', () => {
  it('compares the latest measured month with the first', () => {
    const trend = summarizeMetricTrend([
      { month: '2026-07', value: 40 },
      { month: '2026-08', value: 55 },
      { month: '2026-09', value: 62.5 },
    ]);

    expect(trend).toMatchObject({ direction: 'up', first: { month: '2026-07', value: 40 }, last: { month: '2026-09', value: 62.5 } });
  });

  it('reads a slide and a standstill', () => {
    expect(summarizeMetricTrend([{ month: '2026-08', value: 80 }, { month: '2026-09', value: 70 }])?.direction).toBe('down');
    expect(summarizeMetricTrend([{ month: '2026-08', value: 50 }, { month: '2026-09', value: 50 }])?.direction).toBe('flat');
  });

  it('skips months with nothing to measure instead of treating them as zero', () => {
    const trend = summarizeMetricTrend([
      { month: '2026-07', value: 60 },
      { month: '2026-08', value: null },
      { month: '2026-09', value: 90 },
    ]);

    expect(trend).toMatchObject({ direction: 'up', first: { month: '2026-07' }, last: { month: '2026-09' } });
  });

  it('has no trend to describe with fewer than two measured months', () => {
    expect(summarizeMetricTrend([])).toBeNull();
    expect(summarizeMetricTrend([{ month: '2026-09', value: 50 }, { month: '2026-08', value: null }])).toBeNull();
  });
});
