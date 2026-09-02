import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { Icon } from '@iconify/vue';

import MobileNavLinkRow from '../MobileNavLinkRow.vue';
import type { NavLink } from '../../types';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function createWrapper(link: NavLink) {
  return mount(MobileNavLinkRow, {
    props: { link },
  });
}

describe('MobileNavLinkRow.vue', () => {
  it('renders a divider as a plain separator, not a link', () => {
    const wrapper = createWrapper({ name: '', type: 'divider' });

    expect(wrapper.find('hr, .border-t').exists()).toBe(true);
    expect(wrapper.find('a').exists()).toBe(false);
  });

  it('renders a compact thumbnail for links with an image, not a full-bleed hero card', () => {
    const wrapper = createWrapper({
      name: 'Vasaros stovyklos',
      url: '/stovyklos',
      image: '/img/camp.jpg',
      description: 'Prisijunk prie mūsų',
      type: 'full-height-background-link',
    });

    const img = wrapper.find('img');
    expect(img.exists()).toBe(true);
    expect(img.classes()).not.toContain('absolute');
    expect(wrapper.find('.absolute.inset-0').exists()).toBe(false);
    expect(wrapper.text()).toContain('Vasaros stovyklos');
    expect(wrapper.text()).toContain('Prisijunk prie mūsų');
  });

  it('renders a bold row for category-link', () => {
    const wrapper = createWrapper({ name: 'Dokumentai', url: '/dokumentai', type: 'category-link' });

    expect(wrapper.find('a').classes().join(' ')).toContain('font-bold');
  });

  it('falls back to a plain row for an unknown/absent type', () => {
    const wrapper = createWrapper({ name: 'Struktūra', url: '/struktura' });

    expect(wrapper.find('a').classes().join(' ')).not.toContain('font-bold');
  });

  it('shows the small_text badge when present', () => {
    const wrapper = createWrapper({ name: 'Naujiena', url: '/naujiena', small_text: 'Nauja' });

    expect(wrapper.text()).toContain('Nauja');
  });

  it('shows an icon when link.icon is set', () => {
    const wrapper = createWrapper({ name: 'Kontaktai', url: '/kontaktai', icon: 'contact-card-24-regular' });

    const icon = wrapper.findComponent(Icon);
    expect(icon.exists()).toBe(true);
    expect(icon.props('icon')).toBe('fluent:contact-card-24-regular');
  });

  it('emits close when the link is clicked', async () => {
    const wrapper = createWrapper({ name: 'Struktūra', url: '/struktura' });

    await wrapper.find('a').trigger('click');

    expect(wrapper.emitted('close')).toHaveLength(1);
  });
});
