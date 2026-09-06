import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import CheckControl from '../CheckControl.vue';

/**
 * CheckControl replaces `ui/switch` on the public surface — a switch is a `rounded-full` pill,
 * which survives the surface's zeroed radius scale and renders as the one soft shape on a
 * cornery page. These cover the control contract; the look is covered by the Storybook story.
 */
describe('CheckControl', () => {
  it('exposes checkbox semantics rather than a plain button', () => {
    const wrapper = mount(CheckControl, { props: { modelValue: false, label: 'Didelis kontrastas' } });

    expect(wrapper.attributes('role')).toBe('checkbox');
    expect(wrapper.attributes('aria-checked')).toBe('false');
    expect(wrapper.text()).toContain('Didelis kontrastas');
  });

  it('toggles the model when the row is clicked, not only the box', async () => {
    const wrapper = mount(CheckControl, { props: { modelValue: false } });

    await wrapper.trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([true]);
  });

  it('turns off again when already checked', async () => {
    const wrapper = mount(CheckControl, { props: { modelValue: true } });

    await wrapper.trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([false]);
  });

  it('fills the box with the brand colour and shows a mark only when checked', () => {
    const off = mount(CheckControl, { props: { modelValue: false } });
    expect(off.find('svg').exists()).toBe(false);
    expect(off.html()).not.toContain('bg-brand-fill');

    const on = mount(CheckControl, { props: { modelValue: true } });
    expect(on.find('svg').exists()).toBe(true);
    expect(on.html()).toContain('bg-brand-fill');
  });

  it('lets a slot replace the label', () => {
    const wrapper = mount(CheckControl, {
      props: { modelValue: false, label: 'From prop' },
      slots: { default: 'From slot' },
    });

    expect(wrapper.text()).toBe('From slot');
  });
});
