import { describe, expect, it } from 'vitest';

import { groupStatusFlows, statusFlowInterval } from '../statusFlow';
import type { InstitutionStatusHistoryPoint } from '../../types';

const point = (date: string, count = 1): InstitutionStatusHistoryPoint => ({
  date, all: 10, needs_attention: 2, overdue: 1, approaching: 1, no_activity: 0, recent_check_in: 0, current: 8,
  left_attention: count, entered_attention: count * 2, meetings_registered: count * 3, check_ins_registered: count * 4,
});

describe('status flow buckets', () => {
  it.each([[90, 600, 1], [90, 300, 2], [180, 600, 2], [180, 300, 4]])(
    'adapts %i days to a %ipx plot with %i-day bars', (days, width, interval) => {
      expect(statusFlowInterval(days, width)).toBe(interval);
    },
  );

  it.each([[30, 7], [90, 7], [180, 4]])('groups a %i-day view into the appropriate intervals', (days, count) => {
    const data = Array.from({ length: 7 }, (_, index) => point(`2026-05-0${index + 1}`));
    const buckets = groupStatusFlows(data, '2026-05-01', '2026-05-07', days);
    expect(buckets).toHaveLength(count);
    expect(buckets.reduce((sum, bucket) => sum + bucket.left_attention, 0)).toBe(7);
    expect(buckets.reduce((sum, bucket) => sum + bucket.entered_attention, 0)).toBe(14);
    expect(buckets.at(-1)?.until).toBe('2026-05-07');
  });

  it('anchors bins to the visible range, keeping missing days and a partial final bin in place', () => {
    const buckets = groupStatusFlows([point('2026-05-07', 3), point('2026-05-02', 2)], '2026-05-01', '2026-05-07', 90, 180);
    expect(buckets).toEqual([
      { from: '2026-05-01', until: '2026-05-03', left_attention: 2, entered_attention: 4, meetings_registered: 6, check_ins_registered: 8 },
      { from: '2026-05-07', until: '2026-05-07', left_attention: 3, entered_attention: 6, meetings_registered: 9, check_ins_registered: 12 },
    ]);
  });

  it('uses calendar days across daylight-saving changes', () => {
    const buckets = groupStatusFlows([point('2026-03-29'), point('2026-03-30'), point('2026-03-31')], '2026-03-28', '2026-04-01', 90, 180);
    expect(buckets.map(bucket => [bucket.from, bucket.until, bucket.left_attention]))
      .toEqual([['2026-03-28', '2026-03-30', 2], ['2026-03-31', '2026-04-01', 1]]);
  });
});
