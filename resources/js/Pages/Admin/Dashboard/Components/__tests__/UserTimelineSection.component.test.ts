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
  showActivityRequestsUser: ref(true),
  relatedInstitutionsLoaded: ref(false),
  resetUserFilters: vi.fn(),
  loadRelatedInstitutions: vi.fn(),
};

vi.mock('../../Composables/useTimelineFilters', () => ({
  useTimelineFilters: () => timelineFilters,
}));

const GanttFilterDropdownStub = defineComponent({
  name: 'GanttFilterDropdown',
  props: {
    showActivityRequestsOption: Boolean,
  },
  setup() {
    return () => h('div', { 'data-testid': 'gantt-display-settings' });
  },
});

const TimelineGanttChartStub = defineComponent({
  name: 'TimelineGanttChart',
  props: {
    activityRequests: Array,
    height: String,
    fullscreenActive: Boolean,
  },
  emits: ['fullscreen'],
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

  function mountSection(activityRequests: unknown[] = []) {
    vi.stubGlobal('requestAnimationFrame', (callback: FrameRequestCallback) => {
      callback(0);
      return 1;
    });

    return mount(UserTimelineSection, {
      props: {
        institutions: [],
        meetings: [],
        gaps: [],
        institutionNames: {},
        tenantNames: {},
        activityRequests,
      },
      global: {
        stubs: {
          GanttFilterDropdown: GanttFilterDropdownStub,
          TimelineGanttChart: TimelineGanttChartStub,
          TimelineGanttSkeleton: true,
        },
      },
    });
  }

  it('offers the activity-request toggle only when requests were sent to the user', async () => {
    wrapper = mountSection();
    await nextTick();
    expect(wrapper.getComponent(GanttFilterDropdownStub).props('showActivityRequestsOption')).toBe(false);
    wrapper.unmount();

    const request = { institution_id: 'a', campaign: 'activity_confirmation', period_start: '2026-09-01', period_end: '2026-10-01', sent_at: null, expires_at: '2026-10-20T00:00:00Z', recipients_count: 1 };
    wrapper = mountSection([request]);
    await nextTick();

    expect(wrapper.getComponent(GanttFilterDropdownStub).props('showActivityRequestsOption')).toBe(true);
    expect(wrapper.getComponent(TimelineGanttChartStub).props('activityRequests')).toEqual([request]);
  });

  /**
   * In place, not a modal: the chart that goes full screen is the same instance, so its
   * scroll and zoom survive and dialogs it opens stack above it rather than behind.
   */
  it('takes the same chart full screen and back from its toolbar button', async () => {
    wrapper = mountSection();
    await nextTick();

    const frame = () => wrapper.get('[data-slot="focus-mode-frame"]');
    const chart = wrapper.getComponent(TimelineGanttChartStub);

    expect(frame().attributes('data-active')).toBeUndefined();
    expect(chart.props('height')).toBeUndefined();

    await chart.vm.$emit('fullscreen');

    expect(frame().attributes('data-active')).toBe('true');
    expect(frame().classes()).toContain('fixed');
    expect(chart.props('height')).toBe('100%');
    expect(chart.props('fullscreenActive')).toBe(true);
    expect(wrapper.findAllComponents(TimelineGanttChartStub)).toHaveLength(1);

    await chart.vm.$emit('fullscreen');

    expect(frame().attributes('data-active')).toBeUndefined();
  });
});
