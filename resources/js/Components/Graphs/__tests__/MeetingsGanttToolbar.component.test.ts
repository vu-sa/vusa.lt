import { defineComponent } from 'vue';
import { afterEach, describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import MeetingsGanttToolbar from '../MeetingsGanttToolbar.vue';

import { commonStubs } from '@/tests/stubs';

// reka-ui's Slider relies on ResizeObserver, which jsdom doesn't implement.
// Stub it with a minimal component that preserves the model-value contract.
const SliderStub = defineComponent({
  name: 'SliderStub',
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input type="range" :value="modelValue?.[0]" @input="$emit(\'update:modelValue\', [Number($event.target.value)])" />',
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
        tenantNames: { vusa: 'VU SA', vuif: 'VU IF' },
        dayWidth: 24,
        ...props,
      },
      global: {
        stubs: { ...commonStubs, Slider: SliderStub },
      },
    });
  }

  it('renders the institution count', () => {
    wrapper = mountToolbar({ institutionCount: 7 });

    expect(wrapper.text()).toContain('7');
  });

  it('renders a chip per tenant filter and emits scroll-to-tenant on click', async () => {
    wrapper = mountToolbar({ tenantFilter: ['vusa', 'vuif'] });

    const chips = wrapper.findAll('button').filter(b => b.text() === 'VU SA' || b.text() === 'VU IF');
    expect(chips).toHaveLength(2);

    await chips[0].trigger('click');

    expect(wrapper.emitted('scroll-to-tenant')).toEqual([['vusa']]);
  });

  it('falls back to the raw tenant id when no name is known', () => {
    wrapper = mountToolbar({ tenantFilter: ['unknown-tenant'] });

    expect(wrapper.text()).toContain('unknown-tenant');
  });

  it('emits update:detailsExpanded when the details toggle is clicked', async () => {
    wrapper = mountToolbar({ detailsExpanded: false });

    // Details toggle is the first button inside the right-hand controls group.
    const detailsButton = wrapper.find('button svg path[d^="M3 4a1 1 0 011-1h12"]').element
      .closest('button') as HTMLButtonElement;
    await detailsButton.click();

    expect(wrapper.emitted('update:detailsExpanded')).toEqual([[true]]);
  });

  it('hides the fullscreen button when hideFullscreenButton is true', () => {
    wrapper = mountToolbar({ hideFullscreenButton: true });

    expect(wrapper.find('[data-tour="gantt-fullscreen"]').exists()).toBe(false);
  });

  it('shows the fullscreen button and emits fullscreen when clicked', async () => {
    wrapper = mountToolbar({ hideFullscreenButton: false });

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
