import { defineComponent } from 'vue';
import { afterEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import MeetingsGanttToolbar from '../MeetingsGanttToolbar.vue';
import GanttZoomControl from '../GanttZoomControl.vue';
import { GANTT_ZOOM_STOPS } from '../ganttZoom';

import { commonStubs } from '@/tests/stubs';

// reka-ui's Slider relies on ResizeObserver, which jsdom doesn't implement.
// Stub it with a minimal component that preserves the model-value contract.
const SliderStub = defineComponent({
  name: 'SliderStub',
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input type="range" :value="modelValue?.[0]" @input="$emit(\'update:modelValue\', [Number($event.target.value)])" />',
});

// The menu's spotlight persists through the tutorial-progress API; its own behaviour is reka-ui's.
const GroupByMenuStub = defineComponent({
  name: 'GanttGroupByMenu',
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<button type="button" data-testid="group-by" @click="$emit(\'update:modelValue\', \'type\')">{{ modelValue }}</button>',
});

describe('MeetingsGanttToolbar', () => {
  let wrapper: ReturnType<typeof mount>;

  afterEach(() => {
    wrapper?.unmount();
  });

  function mountToolbar(props: Partial<InstanceType<typeof MeetingsGanttToolbar>['$props']> = {}) {
    return mount(MeetingsGanttToolbar, {
      props: {
        institutionCount: 5,
        dayWidth: 3,
        groupBy: 'tenant',
        ...props,
      },
      global: {
        stubs: { ...commonStubs, Slider: SliderStub, GanttGroupByMenu: GroupByMenuStub },
      },
    });
  }

  it('renders the institution count', () => {
    wrapper = mountToolbar({ institutionCount: 7 });

    expect(wrapper.text()).toContain('7');
  });

  it('zooms through the fine stops, from one pixel per day to nine', async () => {
    wrapper = mountToolbar({ dayWidth: 1 });

    const zoom = wrapper.getComponent(GanttZoomControl);
    expect(zoom.props('stops')).toEqual(GANTT_ZOOM_STOPS);

    const [zoomOut, zoomIn] = zoom.findAll('button');
    expect(zoomOut!.attributes('disabled')).toBeDefined();

    await zoomIn!.trigger('click');
    expect(wrapper.emitted('update:dayWidth')).toEqual([[1.25]]);

    await wrapper.setProps({ dayWidth: 9 });
    expect(zoom.findAll('button')[1]!.attributes('disabled')).toBeDefined();
  });

  it('hands the grouping to its menu and passes a change back up', async () => {
    wrapper = mountToolbar({ groupBy: 'none' });

    const menu = wrapper.get('[data-testid="group-by"]');
    expect(menu.text()).toBe('none');

    await menu.trigger('click');
    expect(wrapper.emitted('update:groupBy')).toEqual([['type']]);
  });

  it('turns the full-screen button into the way back out while in focus mode', () => {
    wrapper = mountToolbar({ fullscreenActive: true });

    const button = wrapper.get('[data-tour="gantt-fullscreen"]');
    expect(button.attributes('aria-pressed')).toBe('true');
    expect(button.attributes('aria-label')).toBe('Išeiti iš viso ekrano');
  });

  it('shows the fullscreen button and emits fullscreen when clicked', async () => {
    wrapper = mountToolbar();

    const fullscreenButton = wrapper.get('[data-tour="gantt-fullscreen"]');
    await fullscreenButton.trigger('click');

    expect(wrapper.emitted('fullscreen')).toHaveLength(1);
  });

  it('hides the legend toggle when showLegend is false', () => {
    wrapper = mountToolbar({ showLegend: false });

    expect(wrapper.find('[data-tour="gantt-legend"]').exists()).toBe(false);
  });

  it('shows a loading indicator when meetingsLoading is true', () => {
    wrapper = mountToolbar({ meetingsLoading: true });

    expect(wrapper.text()).toContain('visak.gantt.loading_meetings');
  });

  it('hides the loading indicator when meetingsLoading is false', () => {
    wrapper = mountToolbar({ meetingsLoading: false });

    expect(wrapper.text()).not.toContain('visak.gantt.loading_meetings');
  });

  it('emits show-legend-modal when the legend toggle is clicked', async () => {
    wrapper = mountToolbar({ showLegend: true });

    await wrapper.get('[data-tour="gantt-legend"]').trigger('click');

    expect(wrapper.emitted('show-legend-modal')).toHaveLength(1);
  });
});
