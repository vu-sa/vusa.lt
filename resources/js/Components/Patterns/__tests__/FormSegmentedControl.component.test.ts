import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import FormSegmentedControl from '../FormSegmentedControl.vue';

describe('FormSegmentedControl', () => {
  const options = [{ value: 1, label: 'One' }, { value: 2, label: 'Two' }];

  it('emits scalar values and keeps ordinary choices in sentence case', async () => {
    const wrapper = mount(FormSegmentedControl, {
      props: { options, modelValue: 1, ariaLabel: 'Choice' },
    });
    await wrapper.findAll('button')[1].trigger('click');
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([2]);
    expect(wrapper.get('button').classes()).toContain('normal-case');

    await wrapper.setProps({ voice: 'brand' });
    expect(wrapper.get('button').classes()).toContain('uppercase');
    expect(wrapper.get('button').classes()).not.toContain('normal-case');
  });

  it('prevents changes in read-only forms', async () => {
    const wrapper = mount(FormSegmentedControl, {
      props: { options, modelValue: 1, ariaLabel: 'Choice', disabled: true },
    });
    expect(wrapper.findAll('button').every(button => button.element.disabled)).toBe(true);
    await wrapper.findAll('button')[1].trigger('click');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
  });
});
