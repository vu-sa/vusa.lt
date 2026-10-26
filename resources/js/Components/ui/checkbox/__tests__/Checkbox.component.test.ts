import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { Check, Minus } from '@lucide/vue';

import Checkbox from '../Checkbox.vue';

describe('Checkbox', () => {
  it.each([
    ['default', 'size-4', '[&_svg]:size-3.5'],
    ['md', 'size-4.5', '[&_svg]:size-3.5'],
    ['lg', 'size-7', '[&_svg]:size-5'],
  ] as const)('sizes the %s box and indicator together', (size, boxClass, iconClass) => {
    const wrapper = mount(Checkbox, { props: { size, modelValue: true } });

    expect(wrapper.get('[role=checkbox]').classes()).toContain(boxClass);
    expect(wrapper.get('[role=checkbox]').classes()).toContain(iconClass);
    expect(wrapper.get('[role=checkbox]').attributes('size')).toBeUndefined();
  });

  it('keeps the indicator foreground stable across all states', async () => {
    const wrapper = mount(Checkbox, { props: { modelValue: false } });
    const checkbox = wrapper.get('[data-slot=checkbox]');

    for (const modelValue of [false, true, 'indeterminate', false] as const) {
      await wrapper.setProps({ modelValue });

      expect(checkbox.classes()).toContain('text-brand-foreground');
      expect(checkbox.classes()).not.toContain('data-[state=checked]:text-brand-foreground');
      expect(checkbox.classes()).not.toContain('data-[state=indeterminate]:text-brand-foreground');
      expect(checkbox.attributes('data-state')).toBe(modelValue === 'indeterminate'
        ? 'indeterminate'
        : modelValue ? 'checked' : 'unchecked');

      if (modelValue) {
        expect(wrapper.get('[data-slot=checkbox-indicator]').classes()).toContain('transition-none');
        expect(wrapper.findComponent(modelValue === 'indeterminate' ? Minus : Check).exists()).toBe(true);
      }
    }
  });

  it('emits the new value when clicked', async () => {
    const wrapper = mount(Checkbox, { props: { modelValue: false } });

    await wrapper.get('[data-slot=checkbox]').trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true]);
  });
});
