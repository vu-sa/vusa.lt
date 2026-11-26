import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCAddPlaceholder from '../RCAddPlaceholder.vue';

describe('RCAddPlaceholder', () => {
  it('renders the label', () => {
    const wrapper = mount(RCAddPlaceholder, { props: { label: 'Add button' } });

    expect(wrapper.get('button').attributes('aria-label')).toBe('Add button');
    expect(wrapper.classes()).toContain('absolute');
    expect(wrapper.classes()).toContain('bg-vusa-red');
  });

  it('renders an icon when given', () => {
    const wrapper = mount(RCAddPlaceholder, {
      props: { label: 'Add image', icon: { template: '<svg class="my-icon" />' } },
    });
    expect(wrapper.find('.my-icon').exists()).toBe(true);
  });

  it('renders no icon when omitted', () => {
    const wrapper = mount(RCAddPlaceholder, { props: { label: 'Add button' } });
    expect(wrapper.find('svg').exists()).toBe(false);
  });

  it('emits click when pressed', async () => {
    const wrapper = mount(RCAddPlaceholder, { props: { label: 'Add button' } });
    await wrapper.trigger('click');
    expect(wrapper.emitted('click')).toHaveLength(1);
  });
});
