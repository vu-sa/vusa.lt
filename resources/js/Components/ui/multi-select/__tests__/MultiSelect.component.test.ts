import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

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

  it('wraps every tag when no limit is set', () => {
    const wrapper = mount(MultiSelect, { props: { modelValue: options, options } });

    for (const { label } of options) {
      expect(wrapper.text()).toContain(label);
    }
    expect(wrapper.find('[data-slot="multi-select-overflow"]').exists()).toBe(false);
  });
});
