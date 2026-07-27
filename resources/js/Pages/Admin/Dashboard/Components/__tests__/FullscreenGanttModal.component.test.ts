import { afterEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref } from 'vue';
import { mount } from '@vue/test-utils';

import FullscreenGanttModal from '../FullscreenGanttModal.vue';

const userTenants = [
  { id: 1, shortname: 'VU SA IF', type: 'padalinys' },
  { id: 2, shortname: 'VU SA FF', type: 'padalinys' },
];

const filters = {
  availableTenantsUser: ref(userTenants),
  userTenantFilter: ref(['1', '2']),
  selectedTenantForGantt: ref(['1']),
  showOnlyWithActivityUser: ref(false),
  showOnlyWithPublicMeetingsUser: ref(false),
  showDutyMembersUser: ref(true),
  showRelatedInstitutionsUser: ref(false),
  showOnlyWithActivityTenant: ref(false),
  showOnlyWithPublicMeetingsTenant: ref(false),
  showDutyMembersTenant: ref(true),
  showActivityStatusTenant: ref(false),
  currentTenant: ref(userTenants[0]),
  setSelectedTenants: vi.fn(),
  setUserTenantFilter: vi.fn(),
};

vi.mock('../../Composables/useTimelineFilters', () => ({
  useTimelineFilters: () => filters,
}));

vi.mock('../../Composables/useGanttSettings', () => ({
  useGanttSettings: () => ({
    showTenantHeaders: ref(true),
  }),
}));

const TenantScopeSelectorStub = defineComponent({
  name: 'TenantScopeSelector',
  props: {
    tenants: Array,
    selectedTenants: Array,
    compact: Boolean,
  },
  setup() {
    return () => h('div', { 'data-testid': 'fullscreen-tenant-scope' });
  },
});

const GanttFilterDropdownStub = defineComponent({
  name: 'GanttFilterDropdown',
  props: {
    tenants: Array,
    triggerLabelOverride: String,
  },
  setup() {
    return () => h('div', { 'data-testid': 'fullscreen-display-settings' });
  },
});

const TimelineGanttChartStub = defineComponent({
  name: 'TimelineGanttChart',
  props: {
    tenantFilter: {
      type: Array,
      default: () => [],
    },
  },
  setup() {
    return () => h('div', { 'data-testid': 'fullscreen-gantt-chart' });
  },
});

const dialogStubs = {
  Dialog: { template: '<div><slot /></div>' },
  DialogContent: { template: '<div><slot /></div>' },
  DialogHeader: { template: '<div><slot /></div>' },
  DialogTitle: { template: '<div><slot /></div>' },
  DialogDescription: { template: '<div><slot /></div>' },
};

describe('FullscreenGanttModal', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
    vi.clearAllMocks();
  });

  function createWrapper(ganttType: 'user' | 'tenant') {
    return mount(FullscreenGanttModal, {
      props: {
        isOpen: true,
        ganttType,
        availableTenants: userTenants,
        userInstitutions: ganttType === 'user'
          ? [{
              id: 'user-institution',
              name: 'User institution',
              tenant: { id: 1, shortname: 'VU SA IF' },
            }]
          : [],
        userMeetings: [],
        userGaps: [],
        userInstitutionNames: {},
        userInstitutionTenant: {},
        tenantInstitutions: [],
        tenantMeetings: [],
        tenantGaps: [],
        tenantInstitutionNames: {},
        tenantInstitutionTenant: {},
        tenantNames: {},
      },
      global: {
        stubs: {
          ...dialogStubs,
          TenantScopeSelector: TenantScopeSelectorStub,
          GanttFilterDropdown: GanttFilterDropdownStub,
          TimelineGanttChart: TimelineGanttChartStub,
        },
      },
    });
  }

  it('shows the personal tenant scope separately from fullscreen display settings', () => {
    wrapper = createWrapper('user');

    const scope = wrapper.getComponent(TenantScopeSelectorStub);
    const displaySettings = wrapper.getComponent(GanttFilterDropdownStub);

    expect(scope.props('selectedTenants')).toEqual(['1', '2']);
    expect(scope.props('compact')).toBe(true);
    expect(displaySettings.props('tenants')).toBeUndefined();
    expect(wrapper.getComponent(TimelineGanttChartStub).props('tenantFilter')).toEqual([]);
  });

  it('shows the administrator tenant scope separately from fullscreen display settings', () => {
    wrapper = createWrapper('tenant');

    const scope = wrapper.getComponent(TenantScopeSelectorStub);
    const displaySettings = wrapper.getComponent(GanttFilterDropdownStub);

    expect(scope.props('selectedTenants')).toEqual(['1']);
    expect(displaySettings.props('tenants')).toBeUndefined();
  });
});
