import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { nextTick, ref } from 'vue';

import ShowAtstovavimas from '@/Pages/Admin/Dashboard/ShowAtstovavimas.vue';
import type { AtstovavimasUser } from '@/Pages/Admin/Dashboard/types';
import { commonStubs } from '@/tests/stubs';

// The page orchestrates ~11 composables and several heavy Gantt/dialog children.
// For a gate-level test we only care that InstitutionStatusSummary mounts when the
// backend says the user may see the tenant overview (`availableTenants`), so the
// data composables are reduced to stable empty refs and the heavy children are
// stubbed. InstitutionStatusSummary itself is replaced by a marker we can assert on.

vi.mock('@/Pages/Admin/Dashboard/Composables/useAtstovavimasData', () => ({
  useAtstovavimasData: () => ({
    institutions: ref([]),
    upcomingMeetings: ref([]),
    sortedMeetings: ref([]),
    allUserMeetings: ref([]),
    userGaps: ref([]),
    institutionsInsights: ref({ attention: [] }),
  }),
}));

vi.mock('@/Pages/Admin/Dashboard/Composables/useTimelineFilters', () => ({
  provideTimelineFilters: () => ({
    availableTenantsUser: ref([]),
    userTenantFilter: ref([]),
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
    showAllInstitutionModal: ref(false),
    showAllMeetingModal: ref(false),
    showMeetingModal: ref(false),
    selectedInstitution: ref(null),
    selectedSuggestedAt: ref(null),
    showFullscreenGantt: ref(false),
    fullscreenGanttType: ref(null),
    showCreateCheckIn: ref(null),
    handleScheduleMeeting: vi.fn(),
    handleShowInstitutionDetails: vi.fn(),
    handleAddCheckIn: vi.fn(),
    onGapCreateMeeting: vi.fn(),
    onGapCreateCheckIn: vi.fn(),
    onGanttFullscreen: vi.fn(),
    onCloseMeetingModal: vi.fn(),
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
    data: ref(null),
    isFetching: ref(false),
    loaded: ref(false),
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

vi.mock('@/Composables/useProductTour', () => ({
  useProductTour: () => ({
    startTour: vi.fn(),
    startTourIfNew: vi.fn(),
    hasCompleted: ref(false),
  }),
}));

vi.mock('@/Composables/useTourProvider', () => ({
  provideTour: vi.fn(),
}));

vi.mock('@/Composables/useFeatureSpotlight', () => ({
  useFeatureSpotlight: () => ({ isDismissed: ref(true), dismiss: vi.fn() }),
}));

vi.mock('@/Composables/useBreadcrumbsUnified', () => ({
  usePageBreadcrumbs: vi.fn(),
  BreadcrumbHelpers: { createBreadcrumbItem: vi.fn(() => ({})) },
}));

const slotStub = (name: string) => ({ name, template: '<slot />' });

const institutionStatusSummaryStub = {
  name: 'InstitutionStatusSummary',
  template: '<div data-testid="institution-status-summary" />',
};

const stubs = {
  ...commonStubs,
  AdminContentPage: slotStub('AdminContentPage'),
  PageHero: slotStub('PageHero'),
  SpotlightPopover: slotStub('SpotlightPopover'),
  PersonalOverviewSection: { name: 'PersonalOverviewSection', template: '<div />' },
  UserTimelineSection: { name: 'UserTimelineSection', template: '<div />' },
  TenantTimelineSection: { name: 'TenantTimelineSection', template: '<div />' },
  TenantScopeSelector: { name: 'TenantScopeSelector', template: '<div />' },
  InstitutionStatusSummary: institutionStatusSummaryStub,
  FullscreenGanttModal: { name: 'FullscreenGanttModal', template: '<div />' },
  InstitutionDataTable: { name: 'InstitutionDataTable', template: '<div />' },
  MeetingDataTable: { name: 'MeetingDataTable', template: '<div />' },
  AddCheckInDialog: { name: 'AddCheckInDialog', template: '<div />' },
  VisakInfoModal: { name: 'VisakInfoModal', template: '<div />' },
};

// The page only reads `id` and `name` from the user (the rest is consumed by the
// mocked `useAtstovavimasData`), so a minimal cast avoids assembling the full entity.
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
    },
    global: { stubs },
  });
}

let wrapper: ReturnType<typeof mount>;

beforeEach(() => {
  // The tenant panel only mounts when its tab is active (reka-ui unmountOnHide),
  // and the page seeds the active tab from ?tab=. Drive both before mounting.
  window.history.replaceState({}, '', '/?tab=tenant');
  // The page defers the heavy timeline section with raf + setTimeout(100).
  // Resolve them synchronously so the deferred block renders inside the test.
  vi.useFakeTimers();
  vi.stubGlobal('requestAnimationFrame', (cb: FrameRequestCallback) => {
    cb(0);
    return 0;
  });
});

afterEach(() => {
  vi.useRealTimers();
  vi.unstubAllGlobals();
  wrapper?.unmount();
  window.history.replaceState({}, '', '/');
});

async function flushDeferred() {
  vi.advanceTimersByTime(200);
  await nextTick();
  await nextTick();
}

describe('InstitutionStatusSummary gate', () => {
  it('renders the summary when the user has visible tenants (duty-role access)', async () => {
    // Regression: previously gated on a hardcoded role-name `isAdmin` that was
    // always false, so coordinators like Lina (institutions.read.* via a duty
    // role) never saw the summary even though the backend granted them access.
    wrapper = createWrapper(1);
    await flushDeferred();

    expect(wrapper.find('[data-testid="institution-status-summary"]').exists()).toBe(true);
  });

  it('hides the summary when the user has no visible tenants', async () => {
    wrapper = createWrapper(0);
    await flushDeferred();

    expect(wrapper.find('[data-testid="institution-status-summary"]').exists()).toBe(false);
  });
});
