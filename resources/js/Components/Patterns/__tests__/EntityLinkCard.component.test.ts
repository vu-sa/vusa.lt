import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import { EntityLinkCard } from '..';

const IconStub = { template: '<svg class="entity-icon" />' };

describe('EntityLinkCard', () => {
  it('renders the eyebrow, title and href', () => {
    const wrapper = mount(EntityLinkCard, {
      props: { href: '/mano/institucijos/1', eyebrow: 'Institucija', title: 'VU SA MIF' },
    });

    expect(wrapper.text()).toContain('Institucija');
    expect(wrapper.text()).toContain('VU SA MIF');
    expect(wrapper.attributes('href')).toBe('/mano/institucijos/1');
  });

  it('renders the icon prop inside the leading tile', () => {
    const wrapper = mount(EntityLinkCard, {
      props: { href: '/x', title: 'Something', icon: IconStub },
    });

    expect(wrapper.find('.entity-icon').exists()).toBe(true);
  });

  it('omits the leading tile entirely when no icon or slot is given', () => {
    const wrapper = mount(EntityLinkCard, {
      props: { href: '/x', title: 'Something' },
    });

    expect(wrapper.find('.entity-icon').exists()).toBe(false);
    expect(wrapper.find('[data-slot="entity-link-card"]').exists()).toBe(true);
  });

  it('lets the leading slot replace the icon tile', () => {
    const wrapper = mount(EntityLinkCard, {
      props: { href: '/x', title: 'Something', icon: IconStub },
      slots: { leading: '<span class="custom-leading">L</span>' },
    });

    expect(wrapper.find('.custom-leading').exists()).toBe(true);
    expect(wrapper.find('.entity-icon').exists()).toBe(false);
  });

  it('renders the optional subtitle only when provided', () => {
    const without = mount(EntityLinkCard, { props: { href: '/x', title: 'T' } });
    expect(without.text()).not.toContain('Sub');

    const withSubtitle = mount(EntityLinkCard, {
      props: { href: '/x', title: 'T', subtitle: 'Sub' },
    });
    expect(withSubtitle.text()).toContain('Sub');
  });

  it('merges caller classes onto the root', () => {
    const wrapper = mount(EntityLinkCard, {
      props: { href: '/x', title: 'T', class: 'mt-4' },
    });

    expect(wrapper.classes()).toContain('mt-4');
  });
});
