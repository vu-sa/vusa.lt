import { describe, expect, it } from 'vitest';

import { calculateInactivePeriods, extractDutyMembers } from '../ganttHelpers';
import type { AtstovavimasInstitution } from '../../types';

const overviewRow = {
  id: 'inst-1',
  name: 'Institucija',
  activity_status: { status: 'healthy', requires_action: false, priority: 0 },
  membership_periods: [
    { user: { id: 'u1', name: 'Lina', profile_photo_path: null }, duty_id: 'd1', start_date: '2025-09-01', end_date: '2026-01-31' },
    { user: { id: 'u2', name: 'Tomas', profile_photo_path: '/p.jpg' }, duty_id: 'd1', start_date: '2026-03-01', end_date: null },
  ],
} as unknown as AtstovavimasInstitution;

describe('extractDutyMembers', () => {
  it('reads the overview rows\' membership periods', () => {
    const members = extractDutyMembers([overviewRow]);

    expect(members.map(member => [member.user.id, member.duty_id, member.end_date])).toEqual([
      ['u1', 'd1', new Date('2026-01-31')],
      ['u2', 'd1', null],
    ]);
  });

  it('carries the Padaliniai activity category to each member', () => {
    const row = { ...overviewRow, member_activity_categories: { u2: 'week' } } as AtstovavimasInstitution;

    expect(extractDutyMembers([row]).map(member => member.user.activityCategory)).toEqual([undefined, 'week']);
  });

  it('finds the gap between membership periods', () => {
    const periods = calculateInactivePeriods([overviewRow], extractDutyMembers([overviewRow]));

    expect(periods).toEqual([{ institution_id: 'inst-1', from: new Date('2026-01-31'), until: new Date('2026-03-01') }]);
  });
});
