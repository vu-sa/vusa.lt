import { describe, expect, it } from 'vitest';

import { termStatus } from '../occupancy';

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
