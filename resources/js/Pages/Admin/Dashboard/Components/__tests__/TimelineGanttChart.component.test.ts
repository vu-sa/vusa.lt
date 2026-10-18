import { describe, expect, it } from 'vitest';
import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';

import TimelineGanttChart from '../TimelineGanttChart.vue';

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
        showOnlyWithActivity: false,
        hideInternalInstitutions: true,
        institutionNames: {},
        tenantNames: {},
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
