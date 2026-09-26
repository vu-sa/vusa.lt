import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick, ref } from 'vue';

import ShowAtstovavimas from '@/Pages/Admin/Dashboard/ShowAtstovavimas.vue';
import type { AtstovavimasUser } from '@/Pages/Admin/Dashboard/types';
import type { HomeFollowedInstitutions } from '@/Components/Home/types';
import { commonStubs } from '@/tests/stubs';

// The page orchestrates several composables and heavy Gantt/dialog children. The data
// composables are reduced to stable refs and the heavy children are stubbed: this test is about the
// personal overview's contract — every number is a link, the padalinys view is a link away for those
// who may see it, and nothing tenant-wide leaks onto this page.

const institution = (id: string, status: string) => ({
  id,
  name: `Institucija ${id}`,
  tenant: { id: '1', shortname: 'VU SA 1' },
  activity_status: { status, requires_action: status !== 'healthy', priority: 1, effective_days_since_activity: 40 },
  meetings: [],
});

const institutions = ref([institution('1', 'overdue'), institution('2', 'healthy'), institution('3', 'approaching')]);

vi.mock('@/Pages/Admin/Dashboard/Composables/useAtstovavimasData', () => ({
  useAtstovavimasData: () => ({
    institutions,
    sortedMeetings: ref([
      { id: 'm1', start_time: '2026-09-01T10:00:00', institution_id: '1', completion_status: 'incomplete' },
      { id: 'm2', start_time: '2026-09-02T10:00:00', institution_id: '2', completion_status: 'complete' },
    ]),
    allUserMeetings: ref([]),
    userGaps: ref([]),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useTimelineFilters', () => ({
  provideTimelineFilters: () => ({
    availableTenantsUser: ref([]),
    userTenantFilter: ref(['1']),
    setUserTenantFilter: vi.fn(),
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

vi.mock('@/Pages/Admin/Dashboard/Composables/useGanttSettings', () => ({
  provideGanttSettings: vi.fn(),
}));

vi.mock('@/Composables/useActionWindow', () => ({
  useActionWindow: () => ({ isOpen: ref(false), open: vi.fn() }),
}));

const startTour = vi.fn();
const startTourIfNew = vi.fn();
vi.mock('@/Composables/useProductTour', () => ({
  useProductTour: () => ({ startTour, startTourIfNew }),
}));
const provideTour = vi.fn();
vi.mock('@/Composables/useTourProvider', () => ({ provideTour: (fn: () => void) => provideTour(fn) }));

const marker = (name: string) => ({ name, template: `<div data-testid="${name}" />` });

const stubs = {
  ...commonStubs,
  InstitutionsNeedingAttention: marker('attention'),
  UpcomingMeetingsList: marker('upcoming'),
  CoordinatorCard: marker('coordinators'),
  WorkspaceSectionTiles: marker('section-tiles'),
  UserTimelineSection: marker('user-timeline'),
  TimelineGanttSkeleton: marker('timeline-skeleton'),
  TenantScopeSelector: marker('tenant-scope-selector'),
  FullscreenGanttModal: marker('fullscreen'),
  AddCheckInDialog: marker('check-in'),
};

const baseUser = { id: '1', name: 'Lina Žilinskaitė' } as unknown as AtstovavimasUser;

function createWrapper(canViewTenantOverview: boolean, followedInstitutions?: HomeFollowedInstitutions, coordinators: { id: string; name: string }[] = []) {
  return mount(ShowAtstovavimas, {
    props: {
      user: baseUser,
      userInstitutions: [],
      canViewTenantOverview,
      openTasksCount: 4,
      coordinators,
      upcomingMeetings: { items: [], total: 0 },
      followedInstitutions,
    },
    global: { stubs },
  });
}

let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  institutions.value = [institution('1', 'overdue'), institution('2', 'healthy'), institution('3', 'approaching')];
  vi.stubGlobal('route', (name: string, params?: Record<string, string>) =>
    `/mano/${name}${params ? `?${new URLSearchParams(params).toString()}` : ''}`);
});

afterEach(() => {
  wrapper?.unmount();
  vi.unstubAllGlobals();
});

describe('the padalinys overview', () => {
  it('is one link away for someone who may see a padalinys', () => {
    wrapper = createWrapper(true);

    expect(wrapper.find('a[href$="/dashboard.atstovavimas.padaliniai"]').exists()).toBe(true);
  });

  it('is not offered to a rep who sees no padalinys', () => {
    wrapper = createWrapper(false);

    expect(wrapper.find('a[href$="/dashboard.atstovavimas.padaliniai"]').exists()).toBe(false);
  });
});

describe('numbers', () => {
  it('counts the personal institutions and links every number', () => {
    wrapper = createWrapper(false);

    const links = wrapper.findAll('[data-slot="overview-numbers"] a');
    expect(links.map(link => link.attributes('data-number'))).toEqual(['overdue', 'approaching', 'incomplete_meetings', 'open_tasks']);
    expect(links.map(link => link.find('span').text())).toEqual(['1', '1', '1', '4']);
    expect(links[2].attributes('href')).toBe('/mano/meetings.index?completion_status=incomplete');
  });
});

describe('layout', () => {
  it('places the KPI strips beside coordinators in two columns when both primary lists are empty', async () => {
    institutions.value = [institution('2', 'healthy')];
    wrapper = createWrapper(false, { total: 0, items: [] }, [{ id: '1', name: 'Koordinatorė' }]);
    await nextTick();

    const primary = wrapper.get('[data-slot="atstovavimas-primary-section"]');
    const numbers = primary.get('[data-slot="overview-numbers"]');
    expect(primary.isVisible()).toBe(true);
    expect(primary.classes()).toContain('gap-12');
    expect(primary.find('aside [data-testid="coordinators"]').exists()).toBe(true);
    expect(numbers.classes()).toContain('lg:grid-cols-2');
    expect(numbers.classes()).toContain('max-md:gap-y-6');
    expect(numbers.classes()).toContain('lg:mt-8');
    expect(numbers.findAll('li')).toHaveLength(4);
    expect(wrapper.findAll('[data-slot="overview-numbers"]')).toHaveLength(1);
  });

  it('lets the KPI strips span the page when no coordinator is shown', async () => {
    institutions.value = [institution('2', 'healthy')];
    wrapper = createWrapper(false, { total: 0, items: [] });
    await nextTick();

    const numbers = wrapper.get('[data-slot="overview-numbers"]');
    const primary = wrapper.get('[data-slot="atstovavimas-primary-section"]');
    expect(primary.isVisible()).toBe(false);
    expect(primary.find('[data-slot="overview-numbers"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="overview-page"]').classes()).toContain('max-md:gap-12');
    expect(numbers.classes()).toContain('lg:grid-cols-4');
    expect(numbers.findAll('li')).toHaveLength(4);
  });

  it('keeps the KPI strips full width when the left column has content', () => {
    wrapper = createWrapper(false, undefined, [{ id: '1', name: 'Koordinatorė' }]);

    const primary = wrapper.get('[data-slot="atstovavimas-primary-section"]');
    expect(primary.find('[data-slot="overview-numbers"]').exists()).toBe(false);
    expect(wrapper.get('[data-slot="overview-numbers"]').classes()).toContain('lg:grid-cols-4');
  });

  it('puts the numbers across the page above the two-column content', () => {
    wrapper = createWrapper(false, { total: 0, items: [] });

    const primary = wrapper.get('[data-slot="atstovavimas-primary-section"]');
    const aside = primary.get('aside');
    const numbers = wrapper.get('[data-slot="overview-numbers"]');
    expect(primary.find('[data-slot="overview-numbers"]').exists()).toBe(false);
    expect(numbers.element.compareDocumentPosition(primary.element) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
    expect(primary.find('[data-testid="attention"]').exists()).toBe(true);
    expect(aside.find('[data-testid="coordinators"]').exists()).toBe(true);
    const status = wrapper.get('[data-slot="overview-status-list"]');
    const timeline = wrapper.get('[data-slot="atstovavimas-timeline-phone-note"]');
    expect(aside.find('[data-slot="overview-status-list"]').exists()).toBe(false);
    expect(timeline.element.compareDocumentPosition(status.element) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
  });

  it('shows followed institutions below the timeline', () => {
    wrapper = createWrapper(false, {
      total: 1,
      items: [{ id: '5', name: 'VU SA', is_muted: false, activity_status: 'healthy' }],
    });

    const timeline = wrapper.get('[data-slot="atstovavimas-timeline-phone-note"]');
    const followed = wrapper.get('[data-slot="followed-institutions"]');
    expect(timeline.element.compareDocumentPosition(followed.element) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
  });

  it('moves empty followed institutions into the all-clear list', () => {
    wrapper = createWrapper(false, { total: 0, items: [] });

    const status = wrapper.get('[data-slot="overview-status-list"]');
    expect(status.text()).toContain('Sekamos institucijos');
    expect(status.text()).toContain('Dar nieko neseki');
    expect(wrapper.find('[data-slot="followed-institutions"]').exists()).toBe(false);
  });

  it('leaves the section tiles to the Padaliniai overview', () => {
    wrapper = createWrapper(false);

    expect(wrapper.find('[data-testid="section-tiles"]').exists()).toBe(false);
  });

  it('carries no tenant-wide trend chart', () => {
    wrapper = createWrapper(true);

    expect(wrapper.find('[data-testid="trend-chart"]').exists()).toBe(false);
  });
});

describe('tour', () => {
  it('registers the overview tour and starts it for someone who has not seen it', () => {
    vi.useFakeTimers();
    startTourIfNew.mockClear();

    wrapper = createWrapper(false);
    vi.advanceTimersByTime(2000);

    expect(provideTour).toHaveBeenCalledWith(startTour);
    expect(startTourIfNew).toHaveBeenCalledOnce();
    vi.useRealTimers();
  });
});
