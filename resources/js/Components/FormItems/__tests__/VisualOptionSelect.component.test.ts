import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { h } from 'vue';

import VisualOptionSelect from '@/Components/FormItems/VisualOptionSelect.vue';
import type { VisualOption } from '@/Components/FormItems/VisualOptionSelect.vue';

const DummyIcon = () => h('svg');

function makeOptions(): VisualOption[] {
  return [
    { value: 'a', label: 'Option A', icon: DummyIcon },
    { value: 'b', label: 'Option B', icon: DummyIcon },
    { value: 'c', label: 'Option C', icon: DummyIcon, disabled: true, description: 'Unavailable' },
  ];
}

describe('VisualOptionSelect.vue', () => {
  it('emits update:modelValue with the clicked option value', async () => {
    const wrapper = mount(VisualOptionSelect, {
      props: { modelValue: 'a', options: makeOptions() },
    });

    const buttons = wrapper.findAll('button');
    const optionB = buttons.find(btn => btn.text().includes('Option B'));
    await optionB?.trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['b']);
  });

  it('does not emit when a disabled option is clicked', async () => {
    const wrapper = mount(VisualOptionSelect, {
      props: { modelValue: 'a', options: makeOptions() },
    });

    const buttons = wrapper.findAll('button');
    const optionC = buttons.find(btn => btn.text().includes('Option C'));
    expect((optionC?.element as HTMLButtonElement).disabled).toBe(true);

    await optionC?.trigger('click');

    expect(wrapper.emitted('update:modelValue')).toBeFalsy();
  });

  it('renders the description for options that have one', () => {
    const wrapper = mount(VisualOptionSelect, {
      props: { modelValue: 'a', options: makeOptions() },
    });

    expect(wrapper.text()).toContain('Unavailable');
  });
});
