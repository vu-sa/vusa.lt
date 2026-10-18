import { afterEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';

import TenantTimelineSection from '../TenantTimelineSection.vue';

const timelineFilters = {
  selectedTenantForGantt: ref(['1']),
  showOnlyWithActivityTenant: ref(false),
  showOnlyWithPublicMeetingsTenant: ref(false),
  hideInternalInstitutionsTenant: ref(false),
  showDutyMembersTenant: ref(true),
  showActivityStatusTenant: ref(false),
  showActivityRequestsTenant: ref(true),
  tenantInstitutionsLoaded: ref(true),
  tenantInstitutionsLoading: ref(false),
  setSelectedTenants: vi.fn(),
  resetTenantFilters: vi.fn(),
};

vi.mock('../../Composables/useTimelineFilters', () => ({
  useTimelineFilters: () => timelineFilters,
}));

const TenantScopeSelectorStub = defineComponent({
  name: 'TenantScopeSelector',
  props: { tenants: Array, selectedTenants: Array },
  emits: ['update:selectedTenants'],
  setup() {
    return () => h('div', { 'data-testid': 'gantt-tenants' });
  },
});

const TimelineGanttChartStub = defineComponent({
  name: 'TimelineGanttChart',
  props: { meetingsLoading: Boolean },
  setup() {
    return () => h('div', { 'data-testid': 'gantt-chart' });
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
    timelineFilters.tenantInstitutionsLoading.value = false;
    vi.clearAllMocks();
  });

  async function createWrapper(availableTenants = tenants) {
    wrapper = mount(TenantTimelineSection, {
      props: {
        availableTenants,
        tenantInstitutions: [{ id: 'a', name: 'VU Senatas' }],
        meetings: [],
        gaps: [],
        institutionNames: {},
        tenantNames: {},
      },
      global: {
        stubs: {
          TenantScopeSelector: TenantScopeSelectorStub,
          GanttFilterDropdown: true,
          TimelineGanttChart: TimelineGanttChartStub,
          TimelineGanttSkeleton: { template: '<div data-testid="gantt-skeleton" />' },
        },
      },
    });
    await new Promise(resolve => requestAnimationFrame(resolve));
    await flushPromises();
  }

  it('picks the Gantt\'s padaliniai in their own control, not in the filters', async () => {
    await createWrapper();

    const picker = wrapper.getComponent(TenantScopeSelectorStub);
    expect(picker.props('tenants')).toEqual(tenants);

    picker.vm.$emit('update:selectedTenants', ['1', '2']);
    expect(timelineFilters.setSelectedTenants).toHaveBeenCalledWith(['1', '2']);
  });

  it('offers no picker when there is only one padalinys to show', async () => {
    await createWrapper([tenants[0]!]);

    expect(wrapper.findComponent(TenantScopeSelectorStub).exists()).toBe(false);
  });

  it('keeps the chart mounted while its rows reload, showing the loading indicator instead', async () => {
    await createWrapper();
    const chart = wrapper.getComponent(TimelineGanttChartStub);

    timelineFilters.tenantInstitutionsLoading.value = true;
    await nextTick();

    expect(wrapper.find('[data-testid="gantt-skeleton"]').exists()).toBe(false);
    expect(wrapper.getComponent(TimelineGanttChartStub).vm).toBe(chart.vm);
    expect(chart.props('meetingsLoading')).toBe(true);
  });
});
