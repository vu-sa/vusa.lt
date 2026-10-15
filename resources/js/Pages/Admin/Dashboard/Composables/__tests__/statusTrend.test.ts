import { describe, expect, it } from 'vitest';

import { summarizeStatusTrend } from '../statusTrend';
import type { InstitutionStatusHistoryPoint } from '../../types';

const point = (date: string, overdue: number): InstitutionStatusHistoryPoint => ({
  date,
  all: 10,
  needs_attention: overdue,
  overdue,
  approaching: 0,
  no_activity: 0,
  current: 10 - overdue,
});

describe('summarizeStatusTrend', () => {
  it('has nothing to say without history', () => {
    expect(summarizeStatusTrend([])).toBeNull();
  });

  it('compares the first and last day of overdue institutions', () => {
    expect(summarizeStatusTrend([point('2026-06-01', 4), point('2026-07-01', 9), point('2026-08-30', 2)]))
      .toEqual({ direction: 'down', from: 4, to: 2 });
    expect(summarizeStatusTrend([point('2026-06-01', 1), point('2026-08-30', 3)]))
      .toEqual({ direction: 'up', from: 1, to: 3 });
  });

  it('reports no change when the ends match, even if the middle moved', () => {
    expect(summarizeStatusTrend([point('2026-06-01', 2), point('2026-07-01', 5), point('2026-08-30', 2)]))
      .toEqual({ direction: 'flat', from: 2, to: 2 });
  });
});
