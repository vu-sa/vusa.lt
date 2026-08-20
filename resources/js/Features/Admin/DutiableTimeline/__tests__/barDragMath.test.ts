import { describe, expect, it } from 'vitest';
import * as d3 from 'd3';

import { describeDragEntry, monthDelta, nearestSnap, shiftByMonths } from '../composables/useBarDrag';
import { parseTimelineDate, toDateString } from '../composables/useDutiableTimelineData';

describe('monthDelta', () => {
  it('rounds to whole columns so a bar never lands between two months', () => {
    expect(monthDelta(0, 64)).toBe(0);
    expect(monthDelta(31, 64)).toBe(0);
    expect(monthDelta(33, 64)).toBe(1);
    expect(monthDelta(128, 64)).toBe(2);
    expect(monthDelta(-128, 64)).toBe(-2);
  });
});

describe('shiftByMonths', () => {
  it('keeps the day of month, which is the whole point of a month-delta drag', () => {
    const moved = shiftByMonths(
      { start: parseTimelineDate('2024-05-18'), end: parseTimelineDate('2025-05-17') },
      2,
    );

    expect(toDateString(moved.start)).toBe('2024-07-18');
    expect(toDateString(moved.end!)).toBe('2025-07-17');
  });

  it('clamps rather than overflowing at a month end', () => {
    const moved = shiftByMonths({ start: parseTimelineDate('2024-01-31'), end: null }, 1);

    expect(toDateString(moved.start)).toBe('2024-02-29');
    expect(moved.end).toBeNull();
  });

  it('leaves an open-ended period open', () => {
    expect(shiftByMonths({ start: parseTimelineDate('2024-07-01'), end: null }, 3).end).toBeNull();
  });

  it('is a no-op at zero, so a click that jitters cannot move anything', () => {
    const period = { start: parseTimelineDate('2024-05-18'), end: parseTimelineDate('2025-05-17') };

    expect(toDateString(shiftByMonths(period, 0).start)).toBe('2024-05-18');
  });
});

describe('nearestSnap', () => {
  const scale = d3.scaleTime()
    .domain([new Date(2024, 0, 1), new Date(2025, 0, 1)])
    .range([0, 366 * 4]); // ~4px a day, so 10px is about two and a half days

  const toPixels = (date: Date) => scale(date);

  it('returns null when nothing is within the snap radius', () => {
    const candidates = [parseTimelineDate('2024-07-01')];
    const px = toPixels(parseTimelineDate('2024-08-15'));

    expect(nearestSnap(candidates, px, toPixels, 10)).toBeNull();
  });

  it('snaps to the closest candidate inside the radius', () => {
    const cadenceStart = parseTimelineDate('2024-07-01');
    const px = toPixels(parseTimelineDate('2024-07-02'));

    expect(toDateString(nearestSnap([cadenceStart], px, toPixels, 10)!)).toBe('2024-07-01');
  });

  it('prefers the nearer of two candidates in range', () => {
    const candidates = [parseTimelineDate('2024-06-30'), parseTimelineDate('2024-07-01')];
    const px = toPixels(parseTimelineDate('2024-07-01'));

    expect(toDateString(nearestSnap(candidates, px, toPixels, 20)!)).toBe('2024-07-01');
  });
});

describe('describeDragEntry', () => {
  it('shows both dates, and marks an open-ended period as unfinished', () => {
    expect(describeDragEntry({
      rowId: 'a', start: parseTimelineDate('2024-07-18'), end: parseTimelineDate('2025-07-17'),
    })).toBe('2024-07-18 → 2025-07-17');

    expect(describeDragEntry({
      rowId: 'a', start: parseTimelineDate('2024-07-18'), end: null,
    })).toBe('2024-07-18 → …');
  });
});
