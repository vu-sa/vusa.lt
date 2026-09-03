import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import SiteFooter from '@/Components/Public/FullWidth/SiteFooter.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function mountFooter(footerNavigation: unknown[] = []) {
  vi.mocked(usePage).mockReturnValue(createMockPage({ footerNavigation }));

  return mount(SiteFooter);
}

describe('SiteFooter.vue', () => {
  it('renders the org contact details from the organization prop', () => {
    const wrapper = mountFooter();

    expect(wrapper.text()).toContain('193077294');
    expect(wrapper.text()).toContain('LT100015645710');
    expect(wrapper.find('a[href="mailto:saskaitos@vusa.lt"]').exists()).toBe(true);
  });

  it('underlines contact and navigation links only on hover', () => {
    const wrapper = mountFooter([
      { id: 1, name: 'Apie mus', url: '/apie', links: [{ id: 11, name: 'Struktūra', url: '/struktura', new_tab: false }] },
    ]);

    for (const link of [
      wrapper.find('a[href="tel:+37052687144"]'),
      wrapper.find('a[href="mailto:saskaitos@vusa.lt"]'),
      wrapper.find('a[href="/apie"]'),
      wrapper.find('a[href="/struktura"]'),
    ]) {
      expect(link.classes()).toContain('no-underline');
      expect(link.classes()).toContain('hover:underline');
    }
  });

  it('renders nothing in the nav slot when there is no footer navigation', () => {
    const wrapper = mountFooter([]);

    expect(wrapper.find('nav[aria-label="navigation.footer_navigation"]').exists()).toBe(false);
  });

  it('renders a footer column as a link when it has a real URL', () => {
    const wrapper = mountFooter([
      { id: 1, name: 'Apie mus', url: '/apie', links: [{ id: 11, name: 'Struktūra', url: '/struktura', new_tab: false }] },
    ]);

    const heading = wrapper.find('a[href="/apie"]');
    expect(heading.exists()).toBe(true);
    expect(heading.text()).toBe('Apie mus');
    expect(wrapper.find('a[href="/struktura"]').text()).toBe('Struktūra');
  });

  it('renders a footer column heading as plain text when its URL is empty or "#"', () => {
    const wrapper = mountFooter([
      { id: 2, name: 'Tik tekstas', url: '#', links: [] },
    ]);

    expect(wrapper.findAll('a').some(a => a.text() === 'Tik tekstas')).toBe(false);
    const heading = wrapper.findAll('span').find(s => s.text() === 'Tik tekstas');
    expect(heading).toBeDefined();
  });

  it('opens a new-tab footer link with target and rel set', () => {
    const wrapper = mountFooter([
      { id: 3, name: 'Iniciatyvos', url: '/iniciatyvos', links: [{ id: 31, name: 'START FM', url: 'https://startfm.lt', new_tab: true }] },
    ]);

    const link = wrapper.find('a[href="https://startfm.lt"]');
    expect(link.attributes('target')).toBe('_blank');
    expect(link.attributes('rel')).toBe('noopener noreferrer');
  });
});
