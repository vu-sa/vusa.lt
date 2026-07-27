import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import InstitutionCompactCard from '../InstitutionCompactCard.vue';

import { commonStubs } from '@/tests/stubs';
import type { AtstovavimosInstitution } from '@/Pages/Admin/Dashboard/types';

const institution = {
  id: '1',
  name: 'Test Institution',
  meetings: [],
  activity_status: {
    status: 'approaching',
    requires_action: true,
    priority: 30,
    periodicity_days: 30,
    effective_days_since_activity: 24,
    progress_percentage: 80,
    last_activity_type: 'meeting',
    last_activity_at: '2025-10-22T10:00:00.000Z',
    last_meeting_at: '2025-10-22T10:00:00.000Z',
    next_meeting_at: null,
    active_check_in_until: null,
  },
} satisfies AtstovavimosInstitution;

describe('InstitutionCompactCard', () => {
  it('renders the backend-provided activity status and effective days', () => {
    const wrapper = mount(InstitutionCompactCard, {
      props: { institution },
      global: {
        stubs: commonStubs,
      },
    });

    expect(wrapper.text()).toContain('visak.activity.activity_status.approaching');
    expect(wrapper.text()).toContain('24 d.');
    expect(wrapper.text()).not.toContain('Senokas susitikimas');
  });

  it('uses a completed check-in as the latest activity reference', () => {
    const checkedInInstitution = {
      ...institution,
      activity_status: {
        ...institution.activity_status,
        status: 'healthy',
        requires_action: false,
        priority: 0,
        effective_days_since_activity: 0,
        progress_percentage: 0,
        last_activity_type: 'check_in',
        last_activity_at: '2026-06-29T21:00:00.000Z',
      },
    } satisfies AtstovavimosInstitution;

    const wrapper = mount(InstitutionCompactCard, {
      props: { institution: checkedInInstitution },
      global: {
        stubs: commonStubs,
      },
    });

    expect(wrapper.text()).toContain('visak.activity.activity_status.healthy');
    expect(wrapper.text()).toContain('0 d.');
    expect(wrapper.text()).not.toContain('29 d.');
    expect(wrapper.text()).not.toContain('visak.activity.activity_status.approaching');
  });
});
