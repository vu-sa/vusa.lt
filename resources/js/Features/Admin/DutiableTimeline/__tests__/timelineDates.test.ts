import { describe, expect, it } from 'vitest';

import { parseTimelineDate, toDateString } from '../composables/useDutiableTimelineData';
import { isMonthAligned, isMonthEndAligned } from '../renderers/renderDutiableBars';

describe('timeline date parsing', () => {
  /**
   * `new Date('2025-07-01')` parses as UTC midnight, which is the previous day in any
   * negative-offset zone. Parsing at local noon is what keeps a term from silently
   * starting on 30 June.
   */
  it('keeps the calendar day regardless of timezone offset', () => {
    const parsed = parseTimelineDate('2025-07-01');

    expect(parsed.getFullYear()).toBe(2025);
    expect(parsed.getMonth()).toBe(6);
    expect(parsed.getDate()).toBe(1);
  });

  it('round-trips through toDateString', () => {
    for (const value of ['2025-07-01', '2026-06-30', '2025-05-18', '2024-02-29']) {
      expect(toDateString(parseTimelineDate(value))).toBe(value);
    }
  });

  it('zero-pads single-digit months and days', () => {
    expect(toDateString(new Date(2025, 0, 5, 12))).toBe('2025-01-05');
  });
});

describe('month-boundary detection', () => {
  /**
   * These drive the notch and dotted lead-in on a bar. A date that is on a boundary
   * must draw nothing, so the marks only ever mean "deliberately off the grid".
   */
  it('treats the 1st as an aligned start', () => {
    expect(isMonthAligned(parseTimelineDate('2025-07-01'))).toBe(true);
    expect(isMonthAligned(parseTimelineDate('2025-05-18'))).toBe(false);
  });

  it('treats the last day of the month as an aligned end', () => {
    expect(isMonthEndAligned(parseTimelineDate('2026-06-30'))).toBe(true);
    expect(isMonthEndAligned(parseTimelineDate('2025-07-31'))).toBe(true);
    expect(isMonthEndAligned(parseTimelineDate('2026-06-11'))).toBe(false);
  });

  it('handles February in a leap year', () => {
    expect(isMonthEndAligned(parseTimelineDate('2024-02-29'))).toBe(true);
    expect(isMonthEndAligned(parseTimelineDate('2025-02-28'))).toBe(true);
    expect(isMonthEndAligned(parseTimelineDate('2024-02-28'))).toBe(false);
  });
});
