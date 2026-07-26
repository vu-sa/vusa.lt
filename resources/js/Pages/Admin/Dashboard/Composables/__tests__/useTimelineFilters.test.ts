import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, nextTick } from 'vue';
import { mount } from '@vue/test-utils';

import {
  getInstitutionTenants,
  normalizeTenantSelection,
  provideTimelineFilters,
  type TimelineFilters,
} from '../useTimelineFilters';
import type { AtstovavimosTenant } from '../../types';

const tenants: AtstovavimosTenant[] = [
  { id: 3, shortname: 'VU SA CHGF', type: 'padalinys' },
  { id: 6, shortname: 'VU SA FsF', type: 'padalinys' },
];

describe('normalizeTenantSelection', () => {
  it('selects the first available tenant when the selection is empty', () => {
    expect(normalizeTenantSelection([], tenants)).toEqual(['3']);
  });

  it('removes stale tenant IDs and keeps valid IDs in available tenant order', () => {
    expect(normalizeTenantSelection(['99', '6', '3'], tenants)).toEqual(['3', '6']);
  });

  it('falls back to the first tenant when all stored IDs are stale', () => {
    expect(normalizeTenantSelection(['99'], tenants)).toEqual(['3']);
  });

  it('returns an empty selection only when no tenants are available', () => {
    expect(normalizeTenantSelection(['3'], [])).toEqual([]);
  });

  it('can default personal filters to all available tenants', () => {
    expect(normalizeTenantSelection([], tenants, 'all')).toEqual(['3', '6']);
  });
});

describe('getInstitutionTenants', () => {
  it('derives tenant options from personal institutions without administrator tenants', () => {
    expect(getInstitutionTenants([
      {
        id: 'institution-1',
        name: 'Institution 1',
        tenant: { id: 6, shortname: 'VU SA FsF', type: 'padalinys' },
        activity_status: {
          status: 'healthy',
          requires_action: false,
          priority: 0,
          periodicity_days: 30,
          effective_days_since_activity: 1,
          progress_percentage: 3,
          last_activity_type: 'meeting',
          last_activity_at: '2026-07-01T10:00:00.000Z',
          last_meeting_at: '2026-07-01T10:00:00.000Z',
          next_meeting_at: null,
          active_check_in_until: null,
        },
      },
    ])).toEqual([
      { id: 6, shortname: 'VU SA FsF', type: 'padalinys' },
    ]);
  });
});

describe('provideTimelineFilters', () => {
  beforeEach(() => {
    localStorage.clear();
    vi.clearAllMocks();
  });

  it('normalizes persisted tenant IDs and prevents clearing the final selection', async () => {
    localStorage.setItem('atstovavimas-timeline-filters', JSON.stringify({
      selectedTenantForGantt: ['99', '6'],
    }));

    let filters: TimelineFilters | undefined;
    const Harness = defineComponent({
      setup() {
        filters = provideTimelineFilters([], tenants);
        return () => null;
      },
    });

    const wrapper = mount(Harness);

    expect(filters?.selectedTenantForGantt.value).toEqual(['6']);

    filters?.setSelectedTenants([]);
    await nextTick();

    expect(filters?.selectedTenantForGantt.value).toEqual(['3']);

    wrapper.unmount();
  });

  it('makes personal tenant filtering available to non-admin users', async () => {
    let filters: TimelineFilters | undefined;
    const institutions = [
      {
        id: 'institution-1',
        name: 'Institution 1',
        tenant: { id: 3, shortname: 'VU SA CHGF' },
        activity_status: {
          status: 'healthy' as const,
          requires_action: false,
          priority: 0,
          periodicity_days: 30,
          effective_days_since_activity: 1,
          progress_percentage: 3,
          last_activity_type: 'meeting' as const,
          last_activity_at: '2026-07-01T10:00:00.000Z',
          last_meeting_at: '2026-07-01T10:00:00.000Z',
          next_meeting_at: null,
          active_check_in_until: null,
        },
      },
      {
        id: 'institution-2',
        name: 'Institution 2',
        tenant: { id: 6, shortname: 'VU SA FsF' },
        activity_status: {
          status: 'healthy' as const,
          requires_action: false,
          priority: 0,
          periodicity_days: 30,
          effective_days_since_activity: 1,
          progress_percentage: 3,
          last_activity_type: 'meeting' as const,
          last_activity_at: '2026-07-01T10:00:00.000Z',
          last_meeting_at: '2026-07-01T10:00:00.000Z',
          next_meeting_at: null,
          active_check_in_until: null,
        },
      },
    ];
    const Harness = defineComponent({
      setup() {
        filters = provideTimelineFilters(institutions, []);
        return () => null;
      },
    });

    const wrapper = mount(Harness);

    expect(filters?.availableTenantsUser.value.map(tenant => String(tenant.id))).toEqual(['3', '6']);
    expect(filters?.userTenantFilter.value).toEqual(['3', '6']);

    filters?.setUserTenantFilter(['6']);
    await nextTick();
    expect(filters?.userTenantFilter.value).toEqual(['6']);

    filters?.setUserTenantFilter([]);
    await nextTick();
    expect(filters?.userTenantFilter.value).toEqual(['3', '6']);

    wrapper.unmount();
  });
});
