import { afterEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref } from 'vue';
import { mount } from '@vue/test-utils';

import TenantTimelineSection from '../TenantTimelineSection.vue';

const timelineFilters = {
  selectedTenantForGantt: ref(['1']),
  showOnlyWithActivityTenant: ref(false),
  showOnlyWithPublicMeetingsTenant: ref(false),
  hideInternalInstitutionsTenant: ref(false),
  showDutyMembersTenant: ref(true),
  showActivityStatusTenant: ref(false),
  tenantInstitutionsLoaded: ref(true),
  tenantInstitutionsLoading: ref(false),
  setSelectedTenants: vi.fn(),
  resetTenantFilters: vi.fn(),
};

vi.mock('../../Composables/useTimelineFilters', () => ({
  useTimelineFilters: () => timelineFilters,
}));

vi.mock('../../Composables/useGanttSettings', () => ({
  useGanttSettings: () => ({
    showTenantHeaders: ref(true),
  }),
}));

const GanttFilterDropdownStub = defineComponent({
  name: 'GanttFilterDropdown',
  props: {
    tenants: Array,
    selectedTenants: Array,
  },
  setup() {
    return () => h('div', { 'data-testid': 'gantt-display-settings' });
  },
});

const tenants = [
  { id: 1, shortname: 'VU SA IF', type: 'padalinys' },
  { id: 2, shortname: 'VU SA FF', type: 'padalinys' },
];

describe('TenantTimelineSection', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
    vi.clearAllMocks();
  });

  function createWrapper(showTenantSelector: boolean) {
    return mount(TenantTimelineSection, {
      props: {
        availableTenants: tenants,
        tenantInstitutions: [],
        meetings: [],
        gaps: [],
        institutionNames: {},
        tenantNames: {},
        institutionTenant: {},
        showTenantSelector,
      },
      global: {
        stubs: {
          GanttFilterDropdown: GanttFilterDropdownStub,
          RepresentativeActivitySection: true,
          TimelineGanttChart: true,
          TimelineGanttSkeleton: true,
        },
      },
    });
  }

  it('keeps the Gantt dropdown limited to display settings for administrators', () => {
    wrapper = createWrapper(false);

    expect(wrapper.getComponent(GanttFilterDropdownStub).props('tenants')).toBeUndefined();
  });

  it('retains tenant selection in the Gantt dropdown for non-administrators', () => {
    wrapper = createWrapper(true);

    expect(wrapper.getComponent(GanttFilterDropdownStub).props('tenants')).toEqual(tenants);
  });
});
