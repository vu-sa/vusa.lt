import { describe, expect, it } from 'vitest';

import { termStatus, termYearSpan } from '../occupancy';

describe('termStatus', () => {
  const today = '2026-09-20';

  it('treats an open-ended term that has started as current', () => {
    expect(termStatus({ start_date: '2026-01-01', end_date: null }, today)).toBe('current');
  });

  it('treats a term with a future end date as current', () => {
    expect(termStatus({ start_date: '2026-01-01', end_date: '2026-12-31' }, today)).toBe('current');
  });

  it('does not count a term that starts in the future', () => {
    expect(termStatus({ start_date: '2026-09-21', end_date: null }, today)).toBe('upcoming');
  });

  it('counts a term starting today as current', () => {
    expect(termStatus({ start_date: '2026-09-20' }, today)).toBe('current');
  });

  it('keeps a term current through its end date', () => {
    expect(termStatus({ start_date: '2026-01-01', end_date: '2026-09-20' }, today)).toBe('current');
    expect(termStatus({ start_date: '2026-01-01', end_date: '2026-09-20' }, '2026-09-21')).toBe('ended');
  });

  it('includes a term that starts and ends on the same day', () => {
    expect(termStatus({ start_date: today, end_date: today }, today)).toBe('current');
  });

  it('accepts full ISO timestamps', () => {
    expect(termStatus({ start_date: '2026-01-01T00:00:00.000000Z', end_date: '2026-09-20T00:00:00.000000Z' }, today)).toBe('current');
  });
});

describe('termYearSpan', () => {
  const today = '2026-09-20';

  it('runs from the first year to now while any term is in force', () => {
    expect(termYearSpan([
      { start_date: '2019-07-01', end_date: '2020-06-30' },
      { start_date: '2025-07-01', end_date: null },
    ], 'dabar', today)).toBe('2019–dabar');
  });

  it('ends at the last term\'s end for a former member', () => {
    expect(termYearSpan([
      { start_date: '2020-07-01', end_date: '2021-06-30' },
      { start_date: '2019-07-01', end_date: '2020-06-30' },
    ], 'dabar', today)).toBe('2019–2021');
  });

  it('collapses a single year', () => {
    expect(termYearSpan([{ start_date: '2020-02-01', end_date: '2020-06-30' }], 'dabar', today)).toBe('2020');
  });

  it('has nothing to say without dated terms', () => {
    expect(termYearSpan([], 'dabar', today)).toBeNull();
  });
});
