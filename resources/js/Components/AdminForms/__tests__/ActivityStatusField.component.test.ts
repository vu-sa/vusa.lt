import { mount } from '@vue/test-utils';
import { expect, it } from 'vitest';

import FormSegmentedControl from '@/Components/Patterns/FormSegmentedControl.vue';
import { commonStubs } from '@/tests/stubs';

import ActivityStatusField from '../ActivityStatusField.vue';

it('uses publishing-style segments with activity labels, icons and semantic colours', () => {
  const wrapper = mount(ActivityStatusField, {
    props: { id: 'activity', modelValue: true },
    global: { stubs: { ...commonStubs, FormSegmentedControl: true } },
  });
  const control = wrapper.findComponent(FormSegmentedControl);
  expect(control.props('options')).toEqual([
    expect.objectContaining({ value: false, label: 'Nebeveikianti', icon: expect.anything(), activeClass: expect.stringContaining('status-neutral') }),
    expect.objectContaining({ value: true, label: 'Veikianti', icon: expect.anything(), activeClass: expect.stringContaining('status-success') }),
  ]);
  control.vm.$emit('update:modelValue', false);
  expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);
});
