import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import NavigationPreview from '@/Features/Admin/NavigationBuilder/NavigationPreview.vue';
import type { AdminNavigationRoot } from '@/Features/Admin/NavigationBuilder/types';

// The dropdown itself is the public nav component and is covered by its own tests; here
// it only stands in for "something that renders at a fixed, desktop-sized width".
const stubs = {
  MainNavigationMenuContent: { template: '<div class="menu-content" />' },
};

function makeRoot(): AdminNavigationRoot {
  return {
    id: 1,
    name: 'Studentams',
    url: '#',
    parent_id: 0,
    lang: 'lt',
    order: 0,
    is_active: true,
    extra_attributes: {},
    cols: 3,
    links: [
      [{ id: 10, name: 'Hero', url: '/a', parent_id: 1, lang: 'lt', order: 0, is_active: true, extra_attributes: { type: 'full-height-background-link', image: 'https://example.com/hero.jpg' } }],
      [],
      [],
    ],
  };
}

describe('NavigationPreview.vue', () => {
  // `MainNavigationMenuContent` renders at the public site's own fixed widths (340px on
  // narrow viewports, up to 800px on xl), which is wider than the admin content area on
  // a phone — it used to push the page sideways instead of scrolling on its own.
  // jsdom computes no layout, so this asserts the scroll container is in place rather
  // than measuring an actual overflow.
  it('wraps the fixed-width dropdown in its own horizontal scroll container', () => {
    const wrapper = mount(NavigationPreview, {
      props: { roots: [makeRoot()] },
      global: { stubs },
    });

    const menu = wrapper.find('.menu-content');
    expect(menu.exists()).toBe(true);

    const scroller = menu.element.closest('.overflow-x-auto');
    expect(scroller).not.toBeNull();
    // The scroller has to sit outside the `inline-block` box, otherwise it is the thing
    // that grows to the dropdown's width and nothing ever clips.
    expect(scroller!.querySelector('.inline-block')).not.toBeNull();
  });

  it('renders one preview per root', () => {
    const wrapper = mount(NavigationPreview, {
      props: { roots: [makeRoot(), { ...makeRoot(), id: 2, name: 'Apie' }] },
      global: { stubs },
    });

    expect(wrapper.findAll('.menu-content')).toHaveLength(2);
  });
});
