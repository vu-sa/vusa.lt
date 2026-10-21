import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick, ref } from 'vue';

import ShowAtstovavimasPadaliniai from '@/Pages/Admin/Dashboard/ShowAtstovavimasPadaliniai.vue';
import { commonStubs } from '@/tests/stubs';

// Data composables are stable refs and the heavy children are stubbed: this test is about the
// padalinys overview's contract — numbers from the tenant summary, never a wrong zero while it
// loads, a capped attention list, the trend chart with its text summary, and none of it for a
// viewer who manages no padalinys (they get the Gantt alone).

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
    selectedTenantForGantt: ref(['1']),
    selectedStatsTenants: selectedTenants,
    setSelectedTenants: vi.fn(),
    setSelectedStatsTenants: vi.fn(),
    tenantInstitutionsLoading: ref(false),
    tenantInstitutionsLoaded: ref(false),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useAtstovavimasActions', () => ({
  useAtstovavimasActions: () => ({
    showCreateCheckIn: ref(null),
    onGapCreateMeeting: vi.fn(),
    onGapCreateCheckIn: vi.fn(),
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

const viewport = vi.hoisted(() => ({ wide: { value: true }, observers: [] as Array<(entries: Array<{ isIntersecting: boolean }>) => void> }));

vi.mock('@vueuse/core', async (importOriginal) => {
  const { ref: vueRef } = await import('vue');
  viewport.wide = vueRef(true);

  return {
    ...(await importOriginal<typeof import('@vueuse/core')>()),
    useMediaQuery: () => viewport.wide,
    useIntersectionObserver: (_target: unknown, callback: (entries: Array<{ isIntersecting: boolean }>) => void) => {
      viewport.observers.push(callback);
    },
  };
});

const showEverything = async () => {
  viewport.observers.forEach(callback => callback([{ isIntersecting: true }]));
  await nextTick();
};

const ganttRowsLoad = vi.fn();

vi.mock('@/Pages/Admin/Dashboard/Composables/useTenantTimelineData', () => ({
  useTenantTimelineData: (routeName?: string) => routeName === 'api.v1.admin.visak.gantt'
    ? { data: ref([]), isFetching: ref(false), loaded: ref(true), load: ganttRowsLoad }
    : ({
        data: ref({
          institutions: [
            ...Array.from({ length: 7 }, (_, i) => tenantInstitution(String(i + 1), i)),
            // A type the settings exclude: drawn in the Gantt, but left out of the numbers and the list.
            { ...tenantInstitution('excluded', 99), in_summary: false },
          ],
          institution_summary: { all: 5, needs_attention: 3, overdue: 2, approaching: 1, no_activity: 0, recent_check_in: 0, current: 2 },
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
      { date: '2026-06-01', all: 5, needs_attention: 4, overdue: 4, approaching: 0, no_activity: 0, recent_check_in: 0, current: 1 },
      { date: '2026-08-30', all: 5, needs_attention: 2, overdue: 2, approaching: 0, no_activity: 0, recent_check_in: 0, current: 3 },
    ]),
    isFetching: ref(false),
    loaded: ref(true),
    error: ref(null),
    pending: ref(false),
    missingDates: ref([]),
    baselineDate: ref(null),
    load: vi.fn(),
  }),
}));

const tenantOpenTasks = ref<{ total: number } | null>({ total: 4 });

const repMetrics = ref<unknown>({
  currentPeriod: null,
  baselinePeriod: null,
  metrics: [{ key: 'recorded_within_week', now: 96, part: 24, whole: 25, baseline: 91.9, baselinePart: 23, baselineWhole: 25 }],
});

vi.mock('@/Composables/useApi', () => ({
  useApi: (url: unknown) => {
    const target = typeof url === 'string' ? url : (url as { value: string }).value;

    return {
      data: target.includes('visak.repMetrics') ? repMetrics : tenantOpenTasks,
      isFetching: ref(false),
      execute: vi.fn(),
      abort: vi.fn(),
      error: ref(null),
    };
  },
}));

vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(true), dismiss: vi.fn() }),
}));

vi.mock('@/Composables/useActionWindow', () => ({
  useActionWindow: () => ({ isOpen: ref(false), open: vi.fn() }),
}));

const marker = (name: string) => ({ name, template: `<div data-testid="${name}" />` });

const stubs = {
  ...commonStubs,
  InstitutionsNeedingAttention: { name: 'InstitutionsNeedingAttention', props: { institutions: Array, limit: Number, title: String }, template: '<div data-testid="attention" />' },
  RepresentativeActivitySection: marker('representatives'),
  TenantTimelineSection: marker('tenant-timeline'),
  TimelineGanttSkeleton: marker('timeline-skeleton'),
  TenantScopeSelector: { name: 'TenantScopeSelector', props: ['tenants', 'label'], template: '<div data-testid="tenant-scope-selector" />' },
  InstitutionStatusTrendChart: marker('trend-chart'),
  AddCheckInDialog: marker('check-in'),
  WorkspaceSectionTiles: marker('section-tiles'),
};

const managed = [{ id: '1', shortname: 'VU SA 1', type: 'padalinys' }, { id: '2', shortname: 'VU SA 2', type: 'padalinys' }];
const everyPadalinys = [...managed, { id: '3', shortname: 'VU SA 3', type: 'padalinys' }];

function createWrapper(canViewTenantTasks = true, statsTenants = managed) {
  return mount(ShowAtstovavimasPadaliniai, {
    props: {
      statsTenants,
      ganttTenants: everyPadalinys,
      defaultGanttTenantIds: ['1'],
      canViewTenantTasks,
    },
    global: { stubs },
  });
}

let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  viewport.wide.value = true;
  viewport.observers.length = 0;
  ganttRowsLoad.mockClear();
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
    expect(links.map(link => link.find('span').text())).toEqual(['2', '1', '0', '0']);
  });

  it('is one row above the attention list and the chart, without a task number', () => {
    wrapper = createWrapper();

    const html = wrapper.html();
    expect(html.indexOf('data-slot="overview-numbers"')).toBeLessThan(html.indexOf('atstovavimas-tenant-primary-section'));
    expect(wrapper.find('[data-slot="overview-numbers"] a[data-number="open_tasks"]').exists()).toBe(false);
    expect(wrapper.find('[role=tab]').exists()).toBe(false);
  });

  it('shows a placeholder, not a wrong zero, while the tenant summary loads', () => {
    tenantLoaded.value = false;
    wrapper = createWrapper();

    expect(wrapper.find('[data-slot="overview-numbers"]').exists()).toBe(false);
    expect(wrapper.findAll('[data-slot="overview-numbers-skeleton"] li')).toHaveLength(4);
    expect(wrapper.find('[data-testid="attention"]').exists()).toBe(false);
  });

  it('sizes the loading metric strip to the viewer’s available counts', () => {
    tenantLoaded.value = false;
    wrapper = createWrapper(false);

    expect(wrapper.findAll('[data-slot="overview-numbers-skeleton"] li')).toHaveLength(4);
  });
});

describe('section tiles', () => {
  it('closes the page with the ViSAK sections', () => {
    wrapper = createWrapper();

    expect(wrapper.find('[data-testid="section-tiles"]').exists()).toBe(true);
  });
});

describe('attention list', () => {
  it('passes every counted institution needing attention, highest priority first, capped at four', async () => {
    wrapper = createWrapper();

    const list = wrapper.getComponent({ name: 'InstitutionsNeedingAttention' });
    const institutions = list.props('institutions') as Array<{ id: string; tenant_name: string | null }>;

    expect(list.props('limit')).toBe(4);
    expect(institutions.map(institution => institution.id)).toEqual(['7', '6', '5', '4', '3', '2', '1']);
    expect(institutions[0]!.tenant_name).toBeNull();
  });

  it('names the padalinys on each row when several are selected', async () => {
    selectedTenants.value = ['1', '2'];
    wrapper = createWrapper();

    const institutions = wrapper.getComponent({ name: 'InstitutionsNeedingAttention' }).props('institutions') as Array<{ tenant_name: string | null }>;

    expect(institutions[0]!.tenant_name).toBe('VU SA 1');
  });
});

describe('upper section', () => {
  it('show a permanent trend beside the attention list with equal width', () => {
    wrapper = createWrapper();

    const section = wrapper.get('[data-slot="atstovavimas-tenant-primary-section"]');
    expect(section.find('[data-testid="trend-chart"]').exists()).toBe(true);
    expect(section.classes()).toContain('lg:grid-cols-2');
    expect(wrapper.find('[data-testid=chart-summary]').exists()).toBe(false);
  });

  it('places outcome metrics outside the upper section and below the timeline', () => {
    wrapper = createWrapper();
    const section = wrapper.get('[data-slot="atstovavimas-tenant-primary-section"]');
    expect(section.find('[data-slot="tenant-rep-metrics"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="atstovavimas-rep-metrics"] [data-slot="tenant-rep-metrics"]').exists()).toBe(true);
    expect(wrapper.get('[data-slot=atstovavimas-tenant-secondary-section]').find('[data-slot=atstovavimas-representatives]').exists()).toBe(true);
    expect(wrapper.text()).not.toContain('metrics.spotlight.action');
    const html = wrapper.html();
    expect(html.indexOf('atstovavimas-rep-metrics')).toBeGreaterThan(html.indexOf('atstovavimas-tenant-primary-section'));
  });
});

describe('scopes', () => {
  it('scopes the header selector to the managed padaliniai and names it as the statistics scope', () => {
    wrapper = createWrapper();

    const selector = wrapper.getComponent({ name: 'TenantScopeSelector' });
    expect(selector.props('tenants')).toEqual(managed);
    expect(selector.props('label')).toBe('visak.tenant_scope.stats_label');
  });

  it('gives a viewer who manages no padalinys only the timeline', async () => {
    wrapper = createWrapper(true, []);
    await showEverything();

    expect(wrapper.find('[data-testid="tenant-scope-selector"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="overview-numbers"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="atstovavimas-tenant-primary-section"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="section-tiles"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('visak.tenant_overview.lead_public');
    expect(ganttRowsLoad).toHaveBeenCalledWith(['1']);
  });
});

describe('timeline loading', () => {
  it('holds the Gantt requests until the timeline is near the viewport', async () => {
    wrapper = createWrapper();
    expect(ganttRowsLoad).not.toHaveBeenCalled();

    await showEverything();

    expect(ganttRowsLoad).toHaveBeenCalledWith(['1']);
  });

  it('never requests the Gantt on a phone, where it is not drawn', async () => {
    viewport.wide.value = false;
    wrapper = createWrapper();

    await showEverything();

    expect(ganttRowsLoad).not.toHaveBeenCalled();
  });
});
