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

  // Mirrors Duty::current_users(): `end_date >= now()` compares a DATE to a DATETIME.
  it('ends a term on its end date, like the server does', () => {
    expect(termStatus({ start_date: '2026-01-01', end_date: '2026-09-20' }, today)).toBe('ended');
    expect(termStatus({ start_date: '2026-01-01', end_date: '2026-09-19' }, today)).toBe('ended');
  });

  it('accepts full ISO timestamps', () => {
    expect(termStatus({ start_date: '2026-01-01T00:00:00.000000Z', end_date: '2026-09-19T00:00:00.000000Z' }, today)).toBe('ended');
  });
});
