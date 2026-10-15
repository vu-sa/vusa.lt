import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import { SectionCard } from '..';

const IconStub = { template: '<svg class="section-icon" />' };

describe('SectionCard', () => {
  it('renders the title and count', () => {
    const wrapper = mount(SectionCard, {
      props: { title: 'Members', count: '3 / 5' },
      slots: { default: '<p class="body">content</p>' },
    });

    expect(wrapper.text()).toContain('Members');
    expect(wrapper.text()).toContain('(3 / 5)');
    expect(wrapper.find('.body').exists()).toBe(true);
  });

  it('omits the count when not provided', () => {
    const wrapper = mount(SectionCard, {
      props: { title: 'Members' },
    });

    expect(wrapper.text()).not.toContain('(');
  });

  it('renders the icon prop', () => {
    const wrapper = mount(SectionCard, {
      props: { title: 'Members', icon: IconStub },
    });

    expect(wrapper.find('.section-icon').exists()).toBe(true);
  });

  it('emits action when the default action button is clicked', async () => {
    const wrapper = mount(SectionCard, {
      props: { title: 'Members', actionLabel: 'All members' },
    });

    const button = wrapper.find('button');
    expect(button.text()).toContain('All members');

    await button.trigger('click');
    expect(wrapper.emitted('action')).toHaveLength(1);
  });

  it('renders a link when actionHref is provided', () => {
    const wrapper = mount(SectionCard, {
      props: { title: 'Members', actionLabel: 'All members', actionHref: '/mano/institutions/1' },
    });

    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toBe('/mano/institutions/1');
  });

  it('shows the empty slot instead of default content when empty', () => {
    const wrapper = mount(SectionCard, {
      props: { title: 'Members', empty: true },
      slots: {
        default: '<p class="body">content</p>',
        empty: '<p class="empty-state">Nothing here</p>',
      },
    });

    expect(wrapper.find('.empty-state').exists()).toBe(true);
    expect(wrapper.find('.body').exists()).toBe(false);
  });
});
