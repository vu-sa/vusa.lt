import { describe, expect, it } from 'vitest';

import { describeInstitutionActivity } from '../institutionActivity';

import type { InstitutionActivityStatus } from '@/Types/InstitutionActivity';

const dates = { day: (value: string) => value, fullDay: (value: string) => value };

const status = (overrides: Partial<InstitutionActivityStatus>): InstitutionActivityStatus => ({
  status: 'healthy',
  requires_action: false,
  priority: 0,
  periodicity_days: 30,
  effective_days_since_activity: null,
  progress_percentage: null,
  last_activity_type: null,
  last_activity_at: null,
  last_meeting_at: null,
  next_meeting_at: null,
  active_check_in_until: null,
  last_check_in_start: null,
  last_check_in_end: null,
  ...overrides,
});

describe('describeInstitutionActivity', () => {
  it('names an ended check-in beside the last meeting it follows', () => {
    const line = describeInstitutionActivity(status({
      status: 'overdue',
      last_meeting_at: '2026-01-30',
      last_check_in_start: '2026-07-01',
      last_check_in_end: '2026-10-16',
    }), dates);

    expect(line).toBe('action_window.institution.last_meeting · action_window.institution.check_in_period');
  });

  it('keeps the last meeting visible while a check-in is active', () => {
    const line = describeInstitutionActivity(status({
      status: 'covered_by_check_in',
      last_meeting_at: '2026-01-30',
      active_check_in_until: '2026-11-03',
    }), dates);

    expect(line).toBe('action_window.institution.check_in_until · action_window.institution.last_meeting_short');
  });

  it('leads with an upcoming meeting and leaves past activity out', () => {
    const line = describeInstitutionActivity(status({
      status: 'covered_by_upcoming_meeting',
      next_meeting_at: '2026-10-24',
      last_meeting_at: '2026-01-30',
      last_check_in_start: '2026-07-01',
      last_check_in_end: '2026-10-16',
    }), dates);

    expect(line).toBe('action_window.institution.next_meeting');
  });

  it('says there were no meetings when only a check-in is known', () => {
    const line = describeInstitutionActivity(status({
      last_check_in_start: '2026-09-01',
      last_check_in_end: '2026-09-30',
    }), dates);

    expect(line).toBe('action_window.institution.no_meetings_yet · action_window.institution.check_in_period');
  });
});
