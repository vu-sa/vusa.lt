import { afterEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';
import { mount } from '@vue/test-utils';

import UserTimelineSection from '../UserTimelineSection.vue';

const timelineFilters = {
  userTenantFilter: ref(['1']),
  showOnlyWithActivityUser: ref(false),
  showOnlyWithPublicMeetingsUser: ref(false),
  hideInternalInstitutionsUser: ref(false),
  showDutyMembersUser: ref(true),
  showRelatedInstitutionsUser: ref(false),
  relatedInstitutionsLoaded: ref(false),
  resetUserFilters: vi.fn(),
  loadRelatedInstitutions: vi.fn(),
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
    triggerLabelOverride: String,
  },
  setup() {
    return () => h('div', { 'data-testid': 'gantt-display-settings' });
  },
});

const TimelineGanttChartStub = defineComponent({
  name: 'TimelineGanttChart',
  props: {
    tenantFilter: Array,
  },
  setup() {
    return () => h('div', { 'data-testid': 'user-timeline-chart' });
  },
});

describe('UserTimelineSection', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
    vi.clearAllMocks();
    vi.unstubAllGlobals();
  });

  it('keeps tenant selection outside display settings without hiding cross-tenant relations', async () => {
    vi.stubGlobal('requestAnimationFrame', (callback: FrameRequestCallback) => {
      callback(0);
      return 1;
    });

    wrapper = mount(UserTimelineSection, {
      props: {
        institutions: [],
        meetings: [],
        gaps: [],
        institutionNames: {},
        tenantNames: {},
        institutionTenant: {},
      },
      global: {
        stubs: {
          GanttFilterDropdown: GanttFilterDropdownStub,
          TimelineGanttChart: TimelineGanttChartStub,
          TimelineGanttSkeleton: true,
        },
      },
    });
    await nextTick();

    const filter = wrapper.getComponent(GanttFilterDropdownStub);
    const chart = wrapper.getComponent(TimelineGanttChartStub);

    expect(filter.props('tenants')).toBeUndefined();
    expect(filter.props('triggerLabelOverride')).toBe('Rodymo nustatymai');
    expect(chart.props('tenantFilter')).toEqual([]);
  });
});
