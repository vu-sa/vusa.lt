import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import MainNavigationMenuContent from '@/Components/Public/Nav/MainNavigationMenuContent.vue';
import type { NavItem, NavLink } from '@/Components/Public/Nav/types';
import { NavigationMenu, NavigationMenuItem, NavigationMenuList } from '@/Components/ui/navigation-menu';

// jsdom cannot evaluate the CSS pipeline (Tailwind isn't compiled in tests, and
// backdrop/opacity/gradient effects render identically as far as jsdom is concerned),
// so these tests assert the exact class strings and inline styles the component
// produces — not what they visually look like. See AGENTS.md / resources/js/CLAUDE.md.
const iconStub = { props: ['icon'], template: '<span class="icon-stub" />' };

function makeItem(links: NavLink[][], cols?: number): NavItem {
  return { id: '1', name: 'Root', cols: cols ?? links.filter(c => c.length > 0).length, links };
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
    // linkTypes.link.textClass is applied to the inner text wrapper, not the anchor. A hovered
    // link changes colour rather than filling or underlining — see the linkTypes docblock.
    const textWrapper = wrapper.findAll('div').find(d => d.classes().includes('group-hover:text-brand'));
    expect(textWrapper).toBeTruthy();
    expect(link.classes()).not.toContain('hover:bg-secondary');
  });

  it('does not make SmartLink reactive when it is passed to the navigation primitive', () => {
    const warning = vi.spyOn(console, 'warn').mockImplementation(() => undefined);

    try {
      mount({
        components: { MainNavigationMenuContent, NavigationMenu, NavigationMenuItem, NavigationMenuList },
        setup: () => ({ item: makeItem([[{ name: 'Dokumentai', url: '/dokumentai', type: 'link' }]]) }),
        template: `
          <NavigationMenu>
            <NavigationMenuList>
              <NavigationMenuItem value="root">
                <MainNavigationMenuContent :item="item" />
              </NavigationMenuItem>
            </NavigationMenuList>
          </NavigationMenu>
        `,
      }, { global: { stubs: { Icon: iconStub } } });

      expect(warning).not.toHaveBeenCalledWith(expect.stringContaining('Component that was made a reactive object'));
    }
    finally {
      warning.mockRestore();
    }
  });

  it('makes every mega-menu link label respond to the underline preference', () => {
    const textLink = mountItem(makeItem([[{ name: 'Dokumentai', url: '/dokumentai', type: 'link' }]]));
    const imageLink = mountItem(makeItem([[{ name: 'Karjera', url: '/karjera', type: 'link', image: '/hero.jpg' }]]));

    [textLink, imageLink].forEach((wrapper) => {
      const label = wrapper.find('a span');

      // jsdom does not compile Tailwind or apply the ancestor selector, so assert the wiring.
      expect(label.classes()).toContain('[.a11y-underline_&]:underline');
      expect(label.classes()).toContain('[.a11y-underline_&]:underline-offset-2');
    });
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

    const gradientLayer = wrapper.findAll('div').find(d => d.classes().includes('bg-ink/40'));
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

  it('marks a featured link with a brand rule', () => {
    const wrapper = mountItem(makeItem([[{ name: 'Svarbu', url: '/s', type: 'link', featured: true }]]));

    const link = wrapper.find('a');
    // A brand-coloured left rule, not a `primary` ring: `primary` is shadcn's neutral ink
    // slot, so a "featured" link rendered in the same colour as every other one.
    expect(link.classes()).toContain('border-brand');
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

  /**
   * `link` vs `block-link` is a content distinction, not a decorative one: a bare headline packs
   * tightly, a link that explains itself gets the room for the explanation. They used to differ
   * only in weight and padding, which is why every existing block-link was migrated down.
   */
  it('packs a bare link tighter than one carrying context', () => {
    const bare = mountItem(makeItem([[{ name: 'Dokumentai', url: '/d', type: 'link' }]]));
    const withContext = mountItem(makeItem([[{ name: 'Dokumentai', url: '/d', type: 'block-link', description: 'Visi VU SA dokumentai' }]]));

    expect(bare.find('a').classes()).toContain('py-1.5');
    expect(withContext.find('a').classes()).toContain('py-2.5');
  });

  it('does not render a description on a bare link, only on one that opted into context', () => {
    const bare = mountItem(makeItem([[{ name: 'Dokumentai', url: '/d', type: 'link', description: 'Liko duomenyse' }]]));
    expect(bare.text()).not.toContain('Liko duomenyse');

    const withContext = mountItem(makeItem([[{ name: 'Dokumentai', url: '/d', type: 'block-link', description: 'Visi VU SA dokumentai' }]]));
    expect(withContext.text()).toContain('Visi VU SA dokumentai');
  });

  /**
   * A column has to hold five image cards without the panel scrolling, so a stack of them is
   * sized as rows rather than as features. Honouring the authored `tall` inside a stack is what
   * made a five-card column overflow.
   */
  it('sizes a lone image card to fill its column and a stack of them compactly', () => {
    const imageLink = (name: string) => ({ name, url: `/${name}`, type: 'link' as const, image: '/hero.jpg' });

    const lone = mountItem(makeItem([[imageLink('A')]]));
    expect(lone.find('a').classes()).toEqual(expect.arrayContaining(['min-h-[11rem]', 'flex-1']));

    const stack = mountItem(makeItem([['A', 'B', 'C', 'D', 'E'].map(imageLink)]));
    const cards = stack.findAll('a');
    expect(cards).toHaveLength(5);
    cards.forEach(card => expect(card.classes()).toContain('min-h-[5.5rem]'));
  });

  it('keeps an authored tall card tall when it is the only one in its column', () => {
    const wrapper = mountItem(makeItem([[
      { name: 'Feature', url: '/f', type: 'link', image: '/hero.jpg', image_height: 'tall' },
    ]]));

    expect(wrapper.find('a').classes()).toContain('min-h-[20rem]');
  });

  it('sizes the panel from its container rather than a fixed dropdown width', () => {
    // The mega menu spans the header measure (NavigationMenuViewport owns that), so the panel
    // must not carry a width of its own — a fixed `lg:w-[650px]` here is what used to make it
    // hug the trigger.
    const wrapper = mount(MainNavigationMenuContent, {
      props: { item: makeItem([[{ name: 'A', url: '/a', type: 'block-link' }]]), isUsedWithoutRoot: true },
      global: { stubs: { Icon: iconStub } },
    });

    const ul = wrapper.find('ul');

    expect(ul.classes()).toContain('w-full');
    expect(ul.classes().some(c => /^(max-)?lg:w-\[/.test(c))).toBe(false);
  });
});
