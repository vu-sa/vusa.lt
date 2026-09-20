import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import ShowAtstovavimas from '@/Pages/Admin/Dashboard/ShowAtstovavimas.vue';
import type { AtstovavimasUser } from '@/Pages/Admin/Dashboard/types';
import { commonStubs } from '@/tests/stubs';

// The page orchestrates ~10 composables and several heavy Gantt/dialog children. The data
// composables are reduced to stable refs and the heavy children are stubbed: this test is about the
// overview's contract — who gets the scope switch, that every number is a link, and that the trend
// chart belongs to the tenant scope only.

const institution = (id: string, status: string) => ({
  id,
  name: `Institucija ${id}`,
  tenant: { id: '1', shortname: 'VU SA 1' },
  activity_status: { status, requires_action: status !== 'healthy', priority: 1, effective_days_since_activity: 40 },
  meetings: [],
});

const tenantLoaded = ref(true);

vi.mock('@/Pages/Admin/Dashboard/Composables/useAtstovavimasData', () => ({
  useAtstovavimasData: () => ({
    institutions: ref([institution('1', 'overdue'), institution('2', 'healthy'), institution('3', 'approaching')]),
    upcomingMeetings: ref([]),
    sortedMeetings: ref([
      { id: 'm1', start_time: '2026-09-01T10:00:00', institution_id: '1', completion_status: 'incomplete' },
      { id: 'm2', start_time: '2026-09-02T10:00:00', institution_id: '2', completion_status: 'complete' },
    ]),
    allUserMeetings: ref([]),
    userGaps: ref([]),
    institutionsInsights: ref({ attention: [] }),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useTimelineFilters', () => ({
  provideTimelineFilters: () => ({
    availableTenantsUser: ref([]),
    userTenantFilter: ref(['1']),
    setUserTenantFilter: vi.fn(),
    selectedTenantForGantt: ref(['1']),
    setSelectedTenants: vi.fn(),
    currentTenant: ref(undefined),
    tenantInstitutionsLoading: ref(false),
    tenantInstitutionsLoaded: ref(false),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useAtstovavimasActions', () => ({
  useAtstovavimasActions: () => ({
    showFullscreenGantt: ref(false),
    fullscreenGanttType: ref(null),
    showCreateCheckIn: ref(null),
    onGapCreateMeeting: vi.fn(),
    onGapCreateCheckIn: vi.fn(),
    onGanttFullscreen: vi.fn(),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useGanttChartData', () => ({
  useGanttChartData: () => ({
    formattedTenantInstitutions: ref([]),
    tenantMeetings: ref([]),
    tenantGaps: ref([]),
    tenantInstitutions: ref([]),
    tenantInstitutionHasActivity: ref({}),
    tenantDutyMembers: ref({}),
    tenantInactivePeriods: ref({}),
    getInstitutionNames: () => ({}),
    getInstitutionTenant: () => ({}),
    getInstitutionHasPublicMeetings: () => ({}),
    getInstitutionPeriodicity: () => ({}),
    getDutyMembersFromInstitutions: () => ({}),
    getInactivePeriodsFromInstitutions: () => ({}),
    getTenantNames: () => ({}),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useGanttSettings', () => ({
  provideGanttSettings: vi.fn(),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useTenantTimelineData', () => ({
  useTenantTimelineData: () => ({
    data: ref({ institutions: [], institution_summary: { all: 5, needs_attention: 3, overdue: 2, approaching: 1, no_activity: 0, current: 2 } }),
    isFetching: ref(false),
    loaded: tenantLoaded,
    load: vi.fn(),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useTenantMeetings', () => ({
  useTenantMeetings: () => ({
    meetings: ref([]),
    pendingWindow: ref(null),
    isFetching: ref(false),
    ensureRange: vi.fn(),
    reset: vi.fn(),
    refresh: vi.fn(() => Promise.resolve()),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useTenantStatusHistory', () => ({
  useTenantStatusHistory: () => ({
    data: ref([
      { date: '2026-06-01', all: 5, needs_attention: 4, overdue: 4, approaching: 0, no_activity: 0, current: 1 },
      { date: '2026-08-30', all: 5, needs_attention: 2, overdue: 2, approaching: 0, no_activity: 0, current: 3 },
    ]),
    isFetching: ref(false),
    loaded: ref(true),
    load: vi.fn(),
  }),
}));

vi.mock('@/Composables/useActionWindow', () => ({
  useActionWindow: () => ({ isOpen: ref(false), open: vi.fn() }),
}));

const marker = (name: string) => ({ name, template: `<div data-testid="${name}" />` });

const stubs = {
  ...commonStubs,
  InstitutionsNeedingAttention: marker('attention'),
  UpcomingMeetingsList: marker('upcoming'),
  UserTimelineSection: marker('user-timeline'),
  TenantTimelineSection: marker('tenant-timeline'),
  TimelineGanttSkeleton: marker('timeline-skeleton'),
  TenantScopeSelector: marker('tenant-scope-selector'),
  InstitutionStatusTrendChart: marker('trend-chart'),
  FullscreenGanttModal: marker('fullscreen'),
  AddCheckInDialog: marker('check-in'),
};

const baseUser = { id: '1', name: 'Lina Žilinskaitė' } as unknown as AtstovavimasUser;
const tenants = (count: number) => Array.from({ length: count }, (_, i) => ({
  id: String(i + 1),
  shortname: `VU SA ${i + 1}`,
  type: 'padalinys',
}));

function createWrapper(availableTenantsCount: number) {
  return mount(ShowAtstovavimas, {
    props: {
      user: baseUser,
      userInstitutions: [],
      availableTenants: tenants(availableTenantsCount),
      openTasksCount: 4,
    },
    global: { stubs },
  });
}

let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  tenantLoaded.value = true;
  vi.stubGlobal('route', (name: string, params?: Record<string, string>) =>
    `/mano/${name}${params ? `?${new URLSearchParams(params).toString()}` : ''}`);
});

afterEach(() => {
  wrapper?.unmount();
  vi.unstubAllGlobals();
  window.history.replaceState({}, '', '/');
});

describe('scope switch', () => {
  it('is offered to a coordinator whose duty role grants visible tenants', () => {
    // Regression: access used to be gated on a hardcoded role name that was always false.
    wrapper = createWrapper(1);

    expect(wrapper.find('[data-slot="overview-scope-switch"]').exists()).toBe(true);
  });

  it('is not offered to a rep with no visible tenants, and there are no page tabs', () => {
    wrapper = createWrapper(0);

    expect(wrapper.find('[data-slot="overview-scope-switch"]').exists()).toBe(false);
    expect(wrapper.find('[role="tab"]').exists()).toBe(false);
  });
});

describe('numbers', () => {
  it('counts the personal institutions and links every number', () => {
    wrapper = createWrapper(0);

    const links = wrapper.findAll('[data-slot="overview-numbers"] a');
    expect(links.map(link => link.attributes('data-number'))).toEqual(['overdue', 'approaching', 'incomplete_meetings', 'open_tasks']);
    expect(links.map(link => link.find('span').text())).toEqual(['1', '1', '1', '4']);
    expect(links[2].attributes('href')).toBe('/mano/meetings.index?completion_status=incomplete');
  });

  it('reads the tenant summary once the tenant scope is chosen', () => {
    window.history.replaceState({}, '', '/?scope=tenant');
    wrapper = createWrapper(1);

    const links = wrapper.findAll('[data-slot="overview-numbers"] a');
    expect(links.map(link => link.find('span').text())).toEqual(['2', '1', '0', '4']);
  });

  it('shows a placeholder, not a wrong zero, while the tenant summary loads', () => {
    tenantLoaded.value = false;
    window.history.replaceState({}, '', '/?scope=tenant');
    wrapper = createWrapper(1);

    expect(wrapper.find('[data-slot="overview-numbers"]').exists()).toBe(false);
  });
});

describe('trend chart', () => {
  it('belongs to the tenant scope and carries a text summary', () => {
    window.history.replaceState({}, '', '/?scope=tenant');
    wrapper = createWrapper(1);

    expect(wrapper.find('[data-testid="trend-chart"]').exists()).toBe(true);
    // 4 overdue at the start of the window, 2 at the end: the caption picks the "fell" sentence.
    expect(wrapper.get('[data-testid="chart-summary"]').text()).toBe('visak.overview.trend.down');
  });

  it('is absent from the personal scope', () => {
    wrapper = createWrapper(1);

    expect(wrapper.find('[data-testid="trend-chart"]').exists()).toBe(false);
  });
});
