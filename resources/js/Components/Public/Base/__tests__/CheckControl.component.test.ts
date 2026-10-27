import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CheckControl from '../CheckControl.vue';

describe('CheckControl', () => {
  it('exposes checkbox semantics rather than a plain button', () => {
    const wrapper = mount(CheckControl, { props: { modelValue: false, label: 'Didelis kontrastas' } });

    expect(wrapper.get('[role=checkbox]').attributes('aria-checked')).toBe('false');
    expect(wrapper.element.tagName).toBe('LABEL');
    expect(wrapper.attributes('for')).toBe(wrapper.get('[role=checkbox]').attributes('id'));
    expect(wrapper.text()).toContain('Didelis kontrastas');
  });

  it('forwards the shared checkbox value to the model', async () => {
    const wrapper = mount(CheckControl, { props: { modelValue: false } });

    await wrapper.get('[role=checkbox]').trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true]);
  });

  it('turns off again when already checked', async () => {
    const wrapper = mount(CheckControl, { props: { modelValue: true } });

    await wrapper.get('[role=checkbox]').trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
  });

  it('fills the box with the brand colour and shows a mark only when checked', () => {
    const off = mount(CheckControl, { props: { modelValue: false } });
    expect(off.find('svg').exists()).toBe(false);
    expect(off.get('[role=checkbox]').attributes('data-state')).toBe('unchecked');

    const on = mount(CheckControl, { props: { modelValue: true } });
    expect(on.find('svg').exists()).toBe(true);
    expect(on.get('[role=checkbox]').attributes('data-state')).toBe('checked');
  });

  it('lets a slot replace the label', () => {
    const wrapper = mount(CheckControl, {
      props: { modelValue: false, label: 'From prop' },
      slots: { default: 'From slot' },
    });

    expect(wrapper.text()).toBe('From slot');
  });

  it.each(['default', 'sm'] as const)('keeps the %s tick colour stable when toggled', async (size) => {
    const wrapper = mount(CheckControl, { props: { size, modelValue: false } });
    const box = wrapper.get('[role=checkbox]');

    for (const modelValue of [false, true, false]) {
      await wrapper.setProps({ modelValue });

      expect(box.classes()).toContain('text-brand-foreground');
      expect(box.attributes('data-state')).toBe(modelValue ? 'checked' : 'unchecked');
    }
  });

  it('forwards control attributes and keeps row styling on the label', async () => {
    const wrapper = mount(CheckControl, {
      props: { id: 'preference', class: 'border-b', modelValue: false },
      attrs: { 'disabled': true, 'data-preference': 'contrast', 'aria-describedby': 'hint' },
    });
    const checkbox = wrapper.get('[data-preference=contrast]');

    expect(wrapper.classes()).toContain('border-b');
    expect(wrapper.attributes('for')).toBe('preference');
    expect(checkbox.attributes('id')).toBe('preference');
    expect(checkbox.attributes('aria-describedby')).toBe('hint');
    expect(checkbox.attributes('disabled')).toBeDefined();
    await checkbox.trigger('click');
    expect(wrapper.emitted('update:modelValue')).toBeUndefined();
  });
});
