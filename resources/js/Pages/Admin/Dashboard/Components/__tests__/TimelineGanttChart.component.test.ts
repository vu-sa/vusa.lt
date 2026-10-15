import { describe, expect, it, vi } from 'vitest';
import { defineComponent, h, ref } from 'vue';
import { mount } from '@vue/test-utils';

import TimelineGanttChart from '../TimelineGanttChart.vue';

vi.mock('../../Composables/useGanttSettings', () => ({
  useGanttSettings: () => ({
    showTenantHeaders: ref(false),
  }),
}));

const MeetingsGanttStub = defineComponent({
  name: 'MeetingsGantt',
  props: {
    institutions: Array,
  },
  setup() {
    return () => h('div');
  },
});

describe('TimelineGanttChart', () => {
  it('passes is_internal through so MeetingsGantt can hide VU SA bodies', () => {
    const wrapper = mount(TimelineGanttChart, {
      props: {
        institutions: [
          { id: 'a', name: 'VU SA Parlamentas', is_internal: true },
          { id: 'b', name: 'VU Senatas', is_internal: false },
        ],
        meetings: [],
        gaps: [],
        tenantFilter: [],
        showOnlyWithActivity: false,
        hideInternalInstitutions: true,
        institutionNames: {},
        tenantNames: {},
        institutionTenant: {},
        emptyMessage: '',
      },
      global: {
        stubs: {
          MeetingsGantt: MeetingsGanttStub,
          GanttLegendModal: true,
        },
      },
    });

    expect(wrapper.getComponent(MeetingsGanttStub).props('institutions')).toEqual([
      expect.objectContaining({ id: 'a', is_internal: true }),
      expect.objectContaining({ id: 'b', is_internal: false }),
    ]);
  });
});
