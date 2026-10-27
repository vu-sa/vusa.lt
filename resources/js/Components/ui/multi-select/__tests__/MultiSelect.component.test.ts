import { describe, expect, it } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import { MultiSelect } from '@/Components/ui/multi-select';

const options = ['MIF', 'FilF', 'EVAF', 'ChGF'].map(label => ({ id: label, label }));

describe('MultiSelect', () => {
  it('collapses tags beyond maxVisibleTags into a +N badge that names the hidden ones', () => {
    const wrapper = mount(MultiSelect, { props: { modelValue: options, options, maxVisibleTags: 2 } });

    expect(wrapper.text()).toContain('MIF');
    expect(wrapper.text()).toContain('FilF');
    expect(wrapper.text()).not.toContain('EVAF');
    const overflow = wrapper.get('[data-slot="multi-select-overflow"]');
    expect(overflow.text()).toBe('+2');
    expect(overflow.attributes('aria-label')).toBe('EVAF, ChGF');
  });

  it('opens the list from anywhere in a one-line field, tags included', async () => {
    const wrapper = mount(MultiSelect, { props: { modelValue: options, options, maxVisibleTags: 2 }, attachTo: document.body });

    expect(wrapper.find('[data-slot="multi-select-overflow"]').exists()).toBe(true);
    expect(document.body.querySelector('[role="listbox"]')).toBeNull();

    await wrapper.get('[data-slot="multi-select-overflow"]').trigger('click');
    await flushPromises();

    expect(document.body.querySelector('[role="listbox"]')?.textContent).toContain('ChGF');
    wrapper.unmount();
  });

  it('wraps every tag when no limit is set', () => {
    const wrapper = mount(MultiSelect, { props: { modelValue: options, options } });

    for (const { label } of options) {
      expect(wrapper.text()).toContain(label);
    }
    expect(wrapper.find('[data-slot="multi-select-overflow"]').exists()).toBe(false);
  });
});
