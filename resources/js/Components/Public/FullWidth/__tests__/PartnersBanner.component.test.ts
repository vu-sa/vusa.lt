import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import PartnersBanner from '@/Components/Public/FullWidth/PartnersBanner.vue';

function makeBanner(overrides: Partial<App.Entities.Banner> = {}): App.Entities.Banner {
  return {
    id: 1,
    title: 'Teisės klinika',
    image_url: null,
    link_url: 'https://example.com',
    lang: 'lt',
    order: 1,
    is_active: 1,
    tenant_id: 1,
    created_at: '',
    updated_at: '',
    ...overrides,
  };
}

describe('PartnersBanner.vue', () => {
  it('renders the title as a text mark when the banner has no logo', () => {
    const wrapper = mount(PartnersBanner, {
      props: { banners: [makeBanner({ image_url: null })] },
    });

    expect(wrapper.text()).toContain('Teisės klinika');
    expect(wrapper.find('img').exists()).toBe(false);
  });

  it('renders the logo image, with the title as its alt text, when one is set', () => {
    const wrapper = mount(PartnersBanner, {
      props: {
        banners: [makeBanner({ image_url: 'https://example.com/logo.png' })],
      },
    });

    const img = wrapper.find('img');
    expect(img.exists()).toBe(true);
    expect(img.attributes('src')).toBe('https://example.com/logo.png');
    expect(img.attributes('alt')).toBe('Teisės klinika');
  });

  it('links each banner to its link_url', () => {
    const wrapper = mount(PartnersBanner, {
      props: { banners: [makeBanner({ link_url: 'https://vu.lt' })] },
    });

    expect(wrapper.find('a[href="https://vu.lt"]').exists()).toBe(true);
  });

  it('renders a plain, non-interactive mark when link_url is empty', () => {
    const wrapper = mount(PartnersBanner, {
      props: { banners: [makeBanner({ link_url: '' })] },
    });

    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.text()).toContain('Teisės klinika');
  });

  it('renders every banner without capping or slicing the list, wrapping the grid instead', () => {
    const banners = Array.from({ length: 8 }, (_, i) => makeBanner({ id: i + 1, title: `Partneris ${i + 1}` }));

    const wrapper = mount(PartnersBanner, { props: { banners } });

    expect(wrapper.findAll('a').length).toBe(banners.length);
    for (const banner of banners) {
      expect(wrapper.text()).toContain(banner.title);
    }
  });

  it('uses the shared ruled grid to center incomplete rows', () => {
    const wrapper = mount(PartnersBanner, { props: { banners: [makeBanner()] } });

    const grid = wrapper.find('[data-slot="ruled-grid"]');
    expect(grid.classes()).toEqual(expect.arrayContaining([
      'flex', 'flex-wrap', 'justify-center', 'border-t',
      '[&>*]:basis-1/2', 'sm:[&>*]:basis-1/3', 'lg:[&>*]:basis-1/5',
    ]));
  });

  it('wires dark:invert on the logo image so a dark logo stays legible on the dark canvas', () => {
    const wrapper = mount(PartnersBanner, {
      props: { banners: [makeBanner({ image_url: 'https://example.com/logo.png' })] },
    });

    const img = wrapper.find('img');
    expect(img.classes()).toEqual(expect.arrayContaining(['grayscale', 'group-hover:grayscale-0', 'dark:invert']));
  });

  it('wires dark:group-hover:grayscale so the colour reveal on hover stays light-mode only', () => {
    const wrapper = mount(PartnersBanner, {
      props: { banners: [makeBanner({ image_url: 'https://example.com/logo.png' })] },
    });

    const img = wrapper.find('img');
    expect(img.classes()).toContain('dark:group-hover:grayscale');
    expect(img.classes()).not.toContain('dark:group-hover:invert-0');
  });
});
