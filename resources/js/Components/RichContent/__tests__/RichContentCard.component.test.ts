import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RichContentCard from '../RichContentCard.vue';

function makeElement(options: Record<string, unknown>) {
  return { type: 'shadcn-card', json_content: {}, options } as unknown as models.ContentPart;
}

describe('RichContentCard', () => {
  it('renders no inline style attributes (no useDark-derived colour logic)', () => {
    const wrapper = mount(RichContentCard, {
      props: { element: makeElement({ variant: 'outline', color: 'red', title: 'T', isTitleColored: true, showIcon: true }) },
    });
    const styled = wrapper.findAll('[style]');
    expect(styled).toHaveLength(0);
  });

  it('never leaks an invalid "variant" DOM attribute onto the card root', () => {
    const wrapper = mount(RichContentCard, {
      props: { element: makeElement({ variant: 'outline', color: 'zinc' }) },
    });
    expect(wrapper.attributes('variant')).toBeUndefined();
  });

  it.each([
    ['outline', 'zinc'],
    ['outline', 'red'],
    ['outline', 'yellow'],
    ['soft', 'zinc'],
    ['soft', 'red'],
    ['soft', 'yellow'],
  ] as const)('renders a distinct surface for variant=%s color=%s', (variant, color) => {
    const wrapper = mount(RichContentCard, { props: { element: makeElement({ variant, color }) } });
    const rootClass = wrapper.attributes('class') ?? '';
    expect(rootClass).toContain('rounded-2xl');
    // No tinted background anywhere — `soft` used to gradient into the color itself,
    // `outline` used a colored border. The surface is always neutral and token-driven, so it
    // follows whichever surface the card is rendered on; colour is at most a 1px accent rail
    // (outline) or nothing (soft).
    expect(rootClass).toMatch(/bg-card|bg-secondary/);
    expect(rootClass).not.toContain('zinc-');

    if (variant === 'outline' && color === 'red') {
      expect(rootClass).toContain('before:bg-brand/70');
    }
    if (variant === 'outline' && color === 'yellow') {
      expect(rootClass).toContain('before:bg-vusa-yellow');
    }
    if (variant === 'soft') {
      expect(rootClass).not.toContain('before:bg-');
    }
  });

  it('does not render a title block when no title is set', () => {
    const wrapper = mount(RichContentCard, { props: { element: makeElement({}) } });
    expect(wrapper.find('[data-slot="card-header"]').exists()).toBe(false);
  });

  it('colours the title only when isTitleColored is set', () => {
    const uncoloured = mount(RichContentCard, { props: { element: makeElement({ color: 'red', title: 'T', isTitleColored: false }) } });
    const coloured = mount(RichContentCard, { props: { element: makeElement({ color: 'red', title: 'T', isTitleColored: true }) } });

    expect(coloured.find('[data-slot="card-title"]').classes().join(' ')).toContain('text-brand');
    expect(uncoloured.find('[data-slot="card-title"]').classes().join(' ')).not.toContain('text-brand');
  });

  it('never renders an icon, even when the deprecated showIcon option is still set on old rows', () => {
    const wrapper = mount(RichContentCard, { props: { element: makeElement({ color: 'yellow', title: 'T', showIcon: true }) } });
    expect(wrapper.find('svg').exists()).toBe(false);
  });

  it('renders slot content inside a .rc-prose wrapper', () => {
    const wrapper = mount(RichContentCard, {
      props: { element: makeElement({}) },
      slots: { default: '<p>Hello</p>' },
    });
    expect(wrapper.find('.rc-prose p').text()).toBe('Hello');
  });
});
