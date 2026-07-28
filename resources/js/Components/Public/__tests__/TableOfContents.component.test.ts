import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import TableOfContents from '../TableOfContents.vue';

/**
 * The mobile floating-button + Sheet variant was removed entirely (it collided with
 * HighlightsFloatingButton at the same fixed position) — this now only renders the
 * desktop sidebar card, unconditionally.
 */
describe('TableOfContents', () => {
  const links = [
    { title: 'Section one', href: '#one', children: [{ title: 'Sub one', href: '#one-a' }] },
    { title: 'Section two', href: '#two' },
  ];

  it('renders the desktop nav with every link and no mobile Sheet/button', () => {
    const wrapper = mount(TableOfContents, { props: { links } });

    const nav = wrapper.find('nav[aria-label="Table of contents"]');
    expect(nav.exists()).toBe(true);
    expect(nav.classes()).toContain('hidden');
    expect(nav.classes()).toContain('lg:block');

    expect(wrapper.text()).toContain('Section one');
    expect(wrapper.text()).toContain('Sub one');
    expect(wrapper.text()).toContain('Section two');

    // No floating button / bottom sheet anywhere in the output.
    expect(wrapper.find('.fixed').exists()).toBe(false);
  });

  it('renders nothing but the empty nav shell when there are no links', () => {
    const wrapper = mount(TableOfContents, { props: { links: [] } });

    expect(wrapper.find('nav').exists()).toBe(true);
    expect(wrapper.findAll('a')).toHaveLength(0);
  });
});
