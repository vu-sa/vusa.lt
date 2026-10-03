import { defineComponent } from 'vue';
import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import GanttZoomControl from '../GanttZoomControl.vue';

// reka-ui's Slider measures itself on mount; jsdom ships no ResizeObserver.
const SliderStub = defineComponent({
  name: 'SliderStub',
  props: ['modelValue'],
  emits: ['update:modelValue'],
  template: '<input type="range" :value="modelValue?.[0]" @input="$emit(\'update:modelValue\', [Number($event.target.value)])" />',
});

function mountZoom(modelValue: number) {
  return mount(GanttZoomControl, {
    props: { modelValue, min: 3, max: 18, step: 4 },
    global: { stubs: { Slider: SliderStub } },
  });
}

describe('GanttZoomControl', () => {
  it('steps in and out by one notch', async () => {
    const wrapper = mountZoom(8);
    const [zoomOut, zoomIn] = wrapper.findAll('button');

    await zoomIn!.trigger('click');
    await zoomOut!.trigger('click');

    expect(wrapper.emitted('update:modelValue')).toEqual([[12], [4]]);
  });

  it('clamps a step to the range and disables the button at its end', async () => {
    const wrapper = mountZoom(16);
    const [zoomOut, zoomIn] = wrapper.findAll('button');

    await zoomIn!.trigger('click');

    expect(wrapper.emitted('update:modelValue')).toEqual([[18]]);
    expect(zoomOut!.attributes('disabled')).toBeUndefined();
    expect(mountZoom(18).findAll('button')[1]!.attributes('disabled')).toBeDefined();
  });

  it('passes the slider value through', async () => {
    const wrapper = mountZoom(8);

    await wrapper.get('input[type="range"]').setValue('10');

    expect(wrapper.emitted('update:modelValue')).toEqual([[10]]);
  });
});
