import { describe, expect, it } from 'vitest';

import type { InstitutionActivityStatusName } from '@/Types/InstitutionActivity';

import { useAtstovavimasData } from '../useAtstovavimasData';
import type { AtstovavimasInstitution, AtstovavimasUser } from '../../types';

const institution = (
  id: string,
  status: InstitutionActivityStatusName,
  priority: number,
): AtstovavimasInstitution => ({
  id,
  name: `Institution ${id}`,
  meetings: [],
  activity_status: {
    status,
    requires_action: ['no_activity', 'approaching', 'overdue'].includes(status),
    priority,
    periodicity_days: 30,
    effective_days_since_activity: status === 'no_activity' ? null : 30,
    progress_percentage: status === 'no_activity' ? null : 100,
    last_activity_type: status === 'no_activity' ? null : 'meeting',
    last_activity_at: status === 'no_activity' ? null : '2025-10-01T10:00:00.000Z',
    last_meeting_at: status === 'no_activity' ? null : '2025-10-01T10:00:00.000Z',
    next_meeting_at: null,
    active_check_in_until: null,
  },
});

describe('useAtstovavimasData', () => {
  it('places check-in date-only values at local calendar midnight', () => {
    const user = { authorization_duties: [{ institution_id: '1' }] } as AtstovavimasUser;
    const data = useAtstovavimasData(user, [{
      ...institution('1', 'healthy', 0),
      check_ins: [{ id: 'check-in', institution_id: '1', user_id: 'user-1', start_date: '2026-03-15', end_date: '2026-03-29' }],
    }]);

    expect(data.userGaps.value[0]!.from).toEqual(new Date(2026, 2, 15));
    expect(data.userGaps.value[0]!.until).toEqual(new Date(2026, 2, 29));
  });

  it('orders attention insights by backend priority', () => {
    const user = {
      authorization_duties: [
        { institution_id: '1' },
        { institution_id: '2' },
        { institution_id: '3' },
      ],
    } as AtstovavimasUser;

    const data = useAtstovavimasData(user, [
      institution('1', 'approaching', 30),
      institution('2', 'healthy', 0),
      institution('3', 'overdue', 50),
    ]);

    expect(data.institutionsInsights.value.attention.map(item => item.id)).toEqual(['3', '1']);
  });

  it('adds the institutions the user serves as secretary for, once, next to their duty ones', () => {
    const user = { authorization_duties: [{ institution_id: '1' }] } as AtstovavimasUser;
    const userInstitutions = [
      institution('1', 'healthy', 0),
      { ...institution('2', 'overdue', 50), is_secretary: true },
    ];

    const data = useAtstovavimasData(user, userInstitutions);

    expect(data.institutions.value.map(item => item.id)).toEqual(['1', '2']);
  });
});
