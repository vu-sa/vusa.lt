import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SearchTabs from '../SearchTabs.vue';

const tabs = [
  { value: 'all', label: 'Viskas', count: 9 },
  { value: 'meetings', label: 'Posėdžiai', count: 1 },
  { value: 'institutions', label: 'Institucijos' },
];

describe('SearchTabs', () => {
  it('renders a button per tab with count badges where provided', () => {
    const wrapper = mount(SearchTabs, { props: { tabs, modelValue: 'all' } });

    const buttons = wrapper.findAll('button');
    expect(buttons).toHaveLength(3);
    expect(wrapper.text()).toContain('Viskas');
    expect(wrapper.text()).toContain('9');
    expect(wrapper.text()).toContain('Posėdžiai');
    expect(wrapper.text()).toContain('1');
  });

  it('emits update:modelValue when a tab is clicked', async () => {
    const wrapper = mount(SearchTabs, { props: { tabs, modelValue: 'all' } });

    await wrapper.findAll('button')[1].trigger('click');

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['meetings']);
  });

  it('marks the active tab', () => {
    const wrapper = mount(SearchTabs, { props: { tabs, modelValue: 'meetings' } });

    const active = wrapper.findAll('button')[1];
    expect(active.classes().join(' ')).toContain('border-primary');
  });
});
