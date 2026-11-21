import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';

import GanttLegendModal from '../GanttLegendModal.vue';
import { lightModeColors } from '../ganttColors';

describe('GanttLegendModal', () => {
  it('shows every meeting colour the chart draws, from the chart palette', () => {
    const wrapper = mount(GanttLegendModal, { props: { isOpen: true }, global: { stubs: commonStubs } });

    const fills = wrapper.findAll('[data-slot="gantt-legend-meeting-statuses"] path').map(path => path.attributes('fill'));

    expect(fills).toEqual([
      lightModeColors.meetingAligned,
      lightModeColors.meetingMixed,
      lightModeColors.meetingMisaligned,
      lightModeColors.meetingComplete,
      lightModeColors.meetingIncomplete,
      lightModeColors.meetingNoItems,
      lightModeColors.meetingUnauthorized,
    ]);
  });
});
