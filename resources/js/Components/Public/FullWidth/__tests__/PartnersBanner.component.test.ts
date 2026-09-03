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

  // jsdom cannot resolve Tailwind's `dark:` variant or render actual flex-wrap line breaks, so
  // a short last row visually centering itself and a logo visibly inverting in dark mode can
  // only be confirmed in a real browser (checked manually). These assert the wiring instead:
  // the classes that produce those behaviors are actually present on the elements.
  it('wires flex-wrap + justify-center on the grid, with a fixed basis per cell, so a short last row centers itself', () => {
    const wrapper = mount(PartnersBanner, { props: { banners: [makeBanner()] } });

    const grid = wrapper.find('[data-slot="section-band"] > div > div');
    expect(grid.classes()).toEqual(expect.arrayContaining(['flex', 'flex-wrap', 'justify-center']));

    const cell = wrapper.find('a');
    expect(cell.classes()).toEqual(expect.arrayContaining(['shrink-0', 'grow-0', 'basis-1/2']));
  });

  it('puts the left rule on each cell instead of the grid container, so a centered row never strands it', () => {
    const wrapper = mount(PartnersBanner, { props: { banners: [makeBanner()] } });

    const grid = wrapper.find('[data-slot="section-band"] > div > div');
    expect(grid.classes()).not.toContain('border-l');

    const cell = wrapper.find('a');
    expect(cell.classes()).toEqual(expect.arrayContaining(['border-l', 'border-b']));
  });

  // Which cell actually renders `border-r` is decided by real `:nth-child`/`:last-child` CSS
  // matching against the column count active at the viewport's breakpoint — jsdom doesn't
  // evaluate that, so it can't be asserted per-cell here (checked manually in a real browser:
  // every row closes on the right, full or partial, with no doubled internal dividers). This
  // only asserts the structural selectors themselves are wired identically on every cell.
  it('wires the responsive nth-child/last-child selectors that close the right edge of every row', () => {
    const banners = Array.from({ length: 3 }, (_, i) => makeBanner({ id: i + 1, title: `Partneris ${i + 1}` }));

    const wrapper = mount(PartnersBanner, { props: { banners } });
    const cells = wrapper.findAll('a');

    for (const cell of cells) {
      expect(cell.classes()).toEqual(expect.arrayContaining([
        'max-sm:[&:is(:nth-child(2n),:last-child)]:border-r',
        'sm:max-lg:[&:is(:nth-child(3n),:last-child)]:border-r',
        'lg:[&:is(:nth-child(5n),:last-child)]:border-r',
      ]));
    }
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
