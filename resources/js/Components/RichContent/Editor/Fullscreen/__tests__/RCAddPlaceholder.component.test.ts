import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCAddPlaceholder from '../RCAddPlaceholder.vue';

describe('RCAddPlaceholder', () => {
  it('renders the label', () => {
    const wrapper = mount(RCAddPlaceholder, { props: { label: 'Add button' } });

    expect(wrapper.text()).toContain('Add button');
    expect(wrapper.classes()).toContain('absolute');
    expect(wrapper.classes()).toContain('bg-vusa-red');
    expect(wrapper.classes()).toContain('animate-pulse');
  });

  it('renders an icon when given', () => {
    const wrapper = mount(RCAddPlaceholder, {
      props: { label: 'Add image', icon: { template: '<svg class="my-icon" />' } },
    });
    expect(wrapper.find('.my-icon').exists()).toBe(true);
  });

  it('renders no icon when omitted', () => {
    const wrapper = mount(RCAddPlaceholder, { props: { label: 'Add button' } });
    expect(wrapper.findComponent({ name: 'component' }).exists()).toBe(false);
  });

  it('emits click when pressed', async () => {
    const wrapper = mount(RCAddPlaceholder, { props: { label: 'Add button' } });
    await wrapper.trigger('click');
    expect(wrapper.emitted('click')).toHaveLength(1);
  });
});
