import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import ShowAtstovavimasPadaliniai from '@/Pages/Admin/Dashboard/ShowAtstovavimasPadaliniai.vue';
import { commonStubs } from '@/tests/stubs';

// Data composables are stable refs and the heavy children are stubbed: this test is about the
// padalinys overview's contract — numbers from the tenant summary, never a wrong zero while it
// loads, a capped attention list and the trend chart with its text summary.

const tenantInstitution = (id: string, priority: number) => ({
  id,
  name: `Institucija ${id}`,
  tenant_id: '1',
  activity_status: { status: 'overdue', requires_action: true, priority, effective_days_since_activity: 40 },
});

const tenantLoaded = ref(true);
const selectedTenants = ref(['1']);

vi.mock('@/Pages/Admin/Dashboard/Composables/useTimelineFilters', () => ({
  provideTimelineFilters: () => ({
    selectedTenantForGantt: selectedTenants,
    setSelectedTenants: vi.fn(),
    tenantInstitutionsLoading: ref(false),
    tenantInstitutionsLoaded: ref(false),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useAtstovavimasActions', () => ({
  useAtstovavimasActions: () => ({
    showFullscreenGantt: ref(false),
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
    tenantDutyMembers: ref([]),
    tenantInactivePeriods: ref([]),
    getInstitutionPeriodicity: () => ({}),
    getTenantNames: () => ({ 1: 'VU SA 1', 2: 'VU SA 2' }),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useGanttSettings', () => ({
  provideGanttSettings: vi.fn(),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useTenantTimelineData', () => ({
  useTenantTimelineData: () => ({
    data: ref({
      institutions: Array.from({ length: 7 }, (_, i) => tenantInstitution(String(i + 1), i)),
      institution_summary: { all: 5, needs_attention: 3, overdue: 2, approaching: 1, no_activity: 0, current: 2 },
    }),
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

const tenantOpenTasks = ref<{ total: number } | null>({ total: 4 });

vi.mock('@/Composables/useApi', () => ({
  useApi: () => ({ data: tenantOpenTasks, execute: vi.fn() }),
}));

vi.mock('@/Composables/useActionWindow', () => ({
  useActionWindow: () => ({ isOpen: ref(false), open: vi.fn() }),
}));

const marker = (name: string) => ({ name, template: `<div data-testid="${name}" />` });

const stubs = {
  ...commonStubs,
  InstitutionsNeedingAttention: { name: 'InstitutionsNeedingAttention', props: ['institutions', 'limit', 'title'], template: '<div data-testid="attention" />' },
  RepresentativeActivitySection: marker('representatives'),
  TenantTimelineSection: marker('tenant-timeline'),
  TimelineGanttSkeleton: marker('timeline-skeleton'),
  TenantScopeSelector: marker('tenant-scope-selector'),
  InstitutionStatusTrendChart: marker('trend-chart'),
  FullscreenGanttModal: marker('fullscreen'),
  AddCheckInDialog: marker('check-in'),
  WorkspaceSectionTiles: marker('section-tiles'),
};

function createWrapper(canViewTenantTasks = true) {
  return mount(ShowAtstovavimasPadaliniai, {
    props: {
      availableTenants: [{ id: '1', shortname: 'VU SA 1', type: 'padalinys' }, { id: '2', shortname: 'VU SA 2', type: 'padalinys' }],
      canViewTenantTasks,
    },
    global: { stubs },
  });
}

let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  tenantLoaded.value = true;
  selectedTenants.value = ['1'];
  vi.stubGlobal('route', (name: string) => `/mano/${name}`);
});

afterEach(() => {
  wrapper?.unmount();
  vi.unstubAllGlobals();
});

describe('numbers', () => {
  it('reads the tenant summary', () => {
    wrapper = createWrapper();

    const links = wrapper.findAll('[data-slot="overview-numbers"] a');
    expect(links.map(link => link.find('span').text())).toEqual(['2', '1', '0', '4']);
  });

  it('counts the open tasks of the selected padaliniai and links to their task list', () => {
    wrapper = createWrapper();

    const tasks = wrapper.get('[data-slot="overview-numbers"] a[data-number="open_tasks"]');
    expect(tasks.find('span').text()).toBe('4');
    expect(tasks.attributes('href')).toBe('/mano/tasks.summary');
  });

  it('leaves the task number out for someone who cannot read padalinys tasks', () => {
    wrapper = createWrapper(false);

    expect(wrapper.find('[data-slot="overview-numbers"] a[data-number="open_tasks"]').exists()).toBe(false);
  });

  it('shows a placeholder, not a wrong zero, while the tenant summary loads', () => {
    tenantLoaded.value = false;
    wrapper = createWrapper();

    expect(wrapper.find('[data-slot="overview-numbers"]').exists()).toBe(false);
  });
});

describe('section tiles', () => {
  it('closes the page with the ViSAK sections', () => {
    wrapper = createWrapper();

    expect(wrapper.find('[data-testid="section-tiles"]').exists()).toBe(true);
  });
});

describe('attention list', () => {
  it('passes every institution needing attention, highest priority first, capped at five', () => {
    wrapper = createWrapper();

    const list = wrapper.getComponent({ name: 'InstitutionsNeedingAttention' });
    const institutions = list.props('institutions') as Array<{ id: string; tenant_name: string | null }>;

    expect(list.props('limit')).toBe(5);
    expect(institutions.map(institution => institution.id)).toEqual(['7', '6', '5', '4', '3', '2', '1']);
    expect(institutions[0]!.tenant_name).toBeNull();
  });

  it('names the padalinys on each row when several are selected', () => {
    selectedTenants.value = ['1', '2'];
    wrapper = createWrapper();

    const institutions = wrapper.getComponent({ name: 'InstitutionsNeedingAttention' }).props('institutions') as Array<{ tenant_name: string | null }>;

    expect(institutions[0]!.tenant_name).toBe('VU SA 1');
  });
});

describe('aside tabs', () => {
  it('open on the trend chart beside the attention list, with a text summary', () => {
    wrapper = createWrapper();

    const aside = wrapper.get('[data-slot="atstovavimas-tenant-primary-section"] aside');
    expect(aside.find('[data-testid="trend-chart"]').exists()).toBe(true);
    // 4 overdue at the start of the window, 2 at the end: the caption picks the "fell" sentence.
    expect(wrapper.get('[data-testid="chart-summary"]').text()).toBe('visak.overview.trend.down');
  });

  it('offer the representatives as the second tab', () => {
    wrapper = createWrapper();

    const triggers = wrapper.findAll('[data-slot="tenant-insights-tabs"] [role="tab"]');
    expect(triggers.map(trigger => trigger.text())).toEqual(['visak.overview.trend.title', 'visak.tenant_overview.representatives']);
  });
});
