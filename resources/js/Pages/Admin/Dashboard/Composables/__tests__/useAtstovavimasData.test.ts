import { describe, expect, it } from 'vitest';

import { useAtstovavimosData } from '../useAtstovavimasData';
import type { AtstovavimosInstitution, AtstovavimosUser } from '../../types';

import type { InstitutionActivityStatusName } from '@/Types/InstitutionActivity';

const institution = (
  id: string,
  status: InstitutionActivityStatusName,
  priority: number,
): AtstovavimosInstitution => ({
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

describe('useAtstovavimosData', () => {
  it('orders attention insights by backend priority', () => {
    const user = {
      current_duties: [
        { institution: institution('1', 'approaching', 30) },
        { institution: institution('2', 'healthy', 0) },
        { institution: institution('3', 'overdue', 50) },
      ],
    } as AtstovavimosUser;

    const data = useAtstovavimosData(user);

    expect(data.institutionsInsights.value.attention.map(item => item.id)).toEqual(['3', '1']);
  });
});
