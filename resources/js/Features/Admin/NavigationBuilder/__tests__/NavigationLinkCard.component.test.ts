import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

import NavigationLinkCard from '@/Features/Admin/NavigationBuilder/NavigationLinkCard.vue';
import type { AdminNavigationLink } from '@/Features/Admin/NavigationBuilder/types';
import { TooltipProvider } from '@/Components/ui/tooltip';

// Deliberately NOT using the shared Tooltip stubs here (see resources/js/CLAUDE.md's
// stubbing policy) — this test exists specifically to catch a real bug in how
// TooltipTrigger's `as-child` interacts with the Switch it wraps, which a stub would
// hide entirely. Basic attribute assertions render fine in jsdom without the popper
// ever needing to actually open, so the "real components by default" rule applies.
function mountWithTooltipProvider(link: AdminNavigationLink) {
  const Wrapper = defineComponent({
    setup() {
      return () => h(TooltipProvider, null, { default: () => h(NavigationLinkCard, { link }) });
    },
  });
  return mount(Wrapper);
}

function makeLink(overrides: Partial<AdminNavigationLink> = {}): AdminNavigationLink {
  return {
    id: 1,
    name: 'Dokumentai',
    url: '/dokumentai',
    parent_id: 1,
    lang: 'lt',
    order: 0,
    is_active: true,
    extra_attributes: {},
    ...overrides,
  };
}

describe('NavigationLinkCard.vue — active switch', () => {
  it('keeps its own data-state (not the Tooltip trigger\'s) when active', () => {
    const wrapper = mountWithTooltipProvider(makeLink({ is_active: true }));

    const toggle = wrapper.find('button[role="switch"]');
    // TooltipTrigger's `as-child` merges its own `data-state` (open/closed) onto
    // whatever single element it wraps. Without a neutral wrapper element between
    // the trigger and the Switch, that merge clobbers the Switch's own
    // `data-state="checked"`/`"unchecked"` — which is exactly what its background
    // color classes key off — leaving the switch visually blank.
    expect(toggle.attributes('data-state')).toBe('checked');
    expect(toggle.attributes('aria-checked')).toBe('true');
  });

  it('keeps its own data-state when inactive', () => {
    const wrapper = mountWithTooltipProvider(makeLink({ is_active: false }));

    const toggle = wrapper.find('button[role="switch"]');
    expect(toggle.attributes('data-state')).toBe('unchecked');
  });

  it('still emits toggle-active when clicked through the tooltip wrapper', async () => {
    const wrapper = mountWithTooltipProvider(makeLink({ is_active: true }));

    const toggle = wrapper.find('button[role="switch"]');
    await toggle.trigger('click');

    expect(wrapper.findComponent(NavigationLinkCard).emitted('toggle-active')?.[0]).toEqual([false]);
  });
});

describe('NavigationLinkCard.vue — full-height-background-link preview', () => {
  it('renders a single root element with the full-height layout classes', () => {
    const link = makeLink({ extra_attributes: { type: 'full-height-background-link', image: 'https://example.com/hero.jpg' } });
    const wrapper = mountWithTooltipProvider(link);

    const root = wrapper.find('.nav-link-card');
    expect(root.attributes('data-link-type')).toBe('full-height-background-link');
    expect(root.classes()).toContain('min-h-24');
    expect(root.classes()).not.toContain('bg-background');
  });

  // The column is a flex-col that stretches to its grid row's height, so the card has
  // to grow into it — `min-h-24` alone only sets a floor, which left a full-height card
  // rendering as a short stub next to taller sibling columns. jsdom computes no layout,
  // so this asserts the class binding rather than a measured height.
  it('grows to fill its column, unlike the compact row layout', () => {
    const fullHeight = mountWithTooltipProvider(makeLink({ extra_attributes: { type: 'full-height-background-link' } }));
    expect(fullHeight.find('.nav-link-card').classes()).toContain('grow');

    const compact = mountWithTooltipProvider(makeLink({ extra_attributes: { type: 'block-link' } }));
    expect(compact.find('.nav-link-card').classes()).not.toContain('grow');
  });

  // Regression guard for the cross-column drag duplicate: a leading comment in the
  // template (kept by the SFC compiler in dev builds) turns this component's root into
  // a Fragment — text anchors + comment + div. SortableJS then drags only the `<div>`
  // out of that range, and Vue can no longer unmount it, so the card stayed behind in
  // the source column. The card must render as exactly one node.
  // `$el` is the tell: an element root resolves to the card `<div>`, while a Fragment
  // root resolves to its leading text anchor instead.
  it.each([
    ['full-height-background-link'],
    ['block-link'],
  ])('renders as one element node (%s), so SortableJS moves the whole card', (type) => {
    const wrapper = mount(NavigationLinkCard, {
      props: { link: makeLink({ extra_attributes: { type: type as 'block-link' } }) },
      global: { stubs: { Tooltip: { template: '<div><slot /></div>' }, TooltipTrigger: { template: '<div><slot /></div>' }, TooltipContent: { template: '<div><slot /></div>' } } },
    });

    const rootNode = wrapper.vm.$el as Node;
    expect(rootNode.nodeType).toBe(Node.ELEMENT_NODE);
    expect((rootNode as HTMLElement).classList).toContain('nav-link-card');
  });

  it('shows the background image when one is set', () => {
    const link = makeLink({ extra_attributes: { type: 'full-height-background-link', image: 'https://example.com/hero.jpg' } });
    const wrapper = mountWithTooltipProvider(link);

    const img = wrapper.find('img');
    expect(img.attributes('src')).toBe('https://example.com/hero.jpg');
  });

  it('shows a placeholder instead of an image when none is set', () => {
    const link = makeLink({ extra_attributes: { type: 'full-height-background-link' } });
    const wrapper = mountWithTooltipProvider(link);

    expect(wrapper.find('img').exists()).toBe(false);
  });

  it('renders the compact row layout for every other type', () => {
    const link = makeLink({ extra_attributes: { type: 'block-link' } });
    const wrapper = mountWithTooltipProvider(link);

    const root = wrapper.find('.nav-link-card');
    expect(root.attributes('data-link-type')).toBe('block-link');
    expect(root.classes()).toContain('bg-background');
    expect(root.classes()).not.toContain('min-h-24');
  });
});
