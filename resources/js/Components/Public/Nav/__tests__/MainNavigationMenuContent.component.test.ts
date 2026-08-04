import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import MainNavigationMenuContent from '@/Components/Public/Nav/MainNavigationMenuContent.vue';
import type { NavItem, NavLink } from '@/Components/Public/Nav/types';

// jsdom cannot evaluate the CSS pipeline (Tailwind isn't compiled in tests, and
// backdrop/opacity/gradient effects render identically as far as jsdom is concerned),
// so these tests assert the exact class strings and inline styles the component
// produces — not what they visually look like. See AGENTS.md / resources/js/CLAUDE.md.
const iconStub = { props: ['icon'], template: '<span class="icon-stub" />' };

function makeItem(links: NavLink[][], cols?: number, menuWidth?: NavItem['menu_width']): NavItem {
  return { id: '1', name: 'Root', cols: cols ?? links.filter(c => c.length > 0).length, menu_width: menuWidth, links };
}

function mountItem(item: NavItem) {
  return mount(MainNavigationMenuContent, {
    props: { item, isUsedWithoutRoot: true },
    global: { stubs: { Icon: iconStub } },
  });
}

describe('MainNavigationMenuContent.vue', () => {
  it('renders a divider without a link', () => {
    const wrapper = mountItem(makeItem([[{ name: '', type: 'divider' }]]));

    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.find('.border-t').exists()).toBe(true);
  });

  it('renders a heading as a non-interactive label', () => {
    const wrapper = mountItem(makeItem([[{ name: 'Skiltis', type: 'heading' }]]));

    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.text()).toContain('Skiltis');
  });

  it('renders a plain link with the "link" style class on its text wrapper', () => {
    const wrapper = mountItem(makeItem([[{ name: 'Dokumentai', url: '/dokumentai', type: 'link' }]]));

    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    // linkTypes.link.textClass is applied to the inner text wrapper, not the anchor.
    const textWrapper = wrapper.findAll('div').find(d => d.classes().includes('hover:underline'));
    expect(textWrapper).toBeTruthy();
  });

  it('renders an image as a card by default (image_render unset)', () => {
    const wrapper = mountItem(makeItem([[{ name: 'Karjera', url: '/karjera', type: 'block-link', image: '/hero.jpg' }]]));

    const img = wrapper.find('img');
    expect(img.exists()).toBe(true);
    // Card branch renders the title inside a dark overlay wrapper with white text.
    expect(wrapper.find('.text-white').exists()).toBe(true);
  });

  it('renders an image as a leading thumbnail when image_render is "thumbnail"', () => {
    const wrapper = mountItem(makeItem([[{
      name: 'Naujiena', url: '/n', type: 'block-link', image: '/thumb.jpg', image_render: 'thumbnail',
    }]]));

    const img = wrapper.find('img');
    expect(img.classes()).toContain('size-10');
    // Thumbnail variant is the text-link branch, not the hero-card branch.
    expect(wrapper.find('.text-white').exists()).toBe(false);
  });

  it('applies the overlay, blur and gradient classes for an image card', () => {
    const wrapper = mountItem(makeItem([[{
      name: 'Hero', url: '/h', type: 'full-height-background-link', image: '/hero.jpg',
      image_overlay: 'heavy', image_blur: 4, image_gradient: 'full',
    }]]));

    const img = wrapper.find('img');
    expect(img.classes()).toContain('opacity-40');
    expect(img.classes()).toContain('blur-[4px]');

    const gradientLayer = wrapper.findAll('div').find(d => d.classes().includes('bg-black/40'));
    expect(gradientLayer).toBeTruthy();
  });

  it('applies image_focal as an inline object-position style', () => {
    const wrapper = mountItem(makeItem([[{
      name: 'Hero', url: '/h', type: 'block-link', image: '/hero.jpg', image_focal: '20% 80%',
    }]]));

    const img = wrapper.find('img');
    expect(img.attributes('style')).toContain('object-position: 20% 80%');
  });

  it('spans multiple columns via col_span', () => {
    const wrapper = mountItem(makeItem([[{ name: 'Wide', url: '/w', type: 'block-link', col_span: 2 }]]));

    const link = wrapper.find('a');
    expect(link.classes()).toContain('lg:col-span-2');
  });

  it('sets target and rel when new_tab is true', () => {
    const wrapper = mountItem(makeItem([[{ name: 'Išorinė', url: 'https://example.com', type: 'link', new_tab: true }]]));

    const link = wrapper.find('a');
    expect(link.attributes('target')).toBe('_blank');
    expect(link.attributes('rel')).toBe('noopener');
  });

  it('passes badge_variant straight through to the Badge component', () => {
    const wrapper = mountItem(makeItem([[{
      name: 'Nauja', url: '/n', type: 'link', small_text: 'NAUJA', badge_variant: 'emerald',
    }]]));

    expect(wrapper.text()).toContain('NAUJA');
  });

  it('marks a featured link with the accent ring class', () => {
    const wrapper = mountItem(makeItem([[{ name: 'Svarbu', url: '/s', type: 'link', featured: true }]]));

    const link = wrapper.find('a');
    expect(link.classes().some(c => c.includes('ring-primary'))).toBe(true);
  });

  it('resolves the column count into a static grid-cols class', () => {
    const wrapper = mountItem(makeItem([
      [{ name: 'A', url: '/a', type: 'link' }],
      [{ name: 'B', url: '/b', type: 'link' }],
    ], 2));

    const ul = wrapper.find('ul');
    expect(ul.classes()).toContain('lg:grid-cols-2');
  });

  it('keys each column independently instead of sharing the root id', () => {
    const wrapper = mountItem(makeItem([
      [{ name: 'A', url: '/a', type: 'link' }],
      [{ name: 'B', url: '/b', type: 'link' }],
    ], 2));

    const listItems = wrapper.findAll('li');
    expect(listItems).toHaveLength(2);
  });

  it('defaults to the wide dropdown width when menu_width is unset, regardless of column count', () => {
    const wrapper = mountItem(makeItem([[{ name: 'A', url: '/a', type: 'link' }]], 1));

    const ul = wrapper.find('ul');
    expect(ul.classes()).toContain('lg:w-[650px]');
  });

  it('narrows the dropdown when menu_width is explicitly set', () => {
    const wrapper = mountItem(makeItem([[{ name: 'A', url: '/a', type: 'link' }]], 1, 'narrow'));

    const ul = wrapper.find('ul');
    expect(ul.classes()).toContain('lg:w-[320px]');
  });

  it('scales the dropdown by column count only when menu_width is "auto"', () => {
    const wrapper = mountItem(makeItem([
      [{ name: 'A', url: '/a', type: 'link' }],
      [{ name: 'B', url: '/b', type: 'link' }],
    ], 2, 'auto'));

    const ul = wrapper.find('ul');
    expect(ul.classes()).toContain('lg:w-[500px]');
  });
});
