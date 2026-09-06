import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import DisplayHeading from '../DisplayHeading.vue';
import SectionBand from '../SectionBand.vue';
import TagChip from '../TagChip.vue';

/**
 * These assert wiring only — which classes and elements are produced. Whether the brand rule
 * actually renders red on light and amber on dark cannot be checked here: jsdom does not
 * resolve Tailwind's `dark:` variant or the `[data-surface]` token scope. That is covered by the
 * Storybook catalogue (Public/Base/*), which runs in a real browser with a theme toolbar.
 */
describe('DisplayHeading', () => {
  it('renders the heading at the requested level', () => {
    const wrapper = mount(DisplayHeading, { props: { title: 'Renginiai', as: 'h1' } });

    expect(wrapper.find('h1').text()).toBe('Renginiai');
  });

  it('hangs the block off the brand rule by default and can drop it', () => {
    const withRule = mount(DisplayHeading, { props: { title: 'A' } });
    expect(withRule.get('[data-slot="display-heading"]').classes()).toContain('border-brand');

    const without = mount(DisplayHeading, { props: { title: 'A', rule: false } });
    expect(without.get('[data-slot="display-heading"]').classes()).not.toContain('border-brand');
  });

  it('omits the eyebrow and lead entirely when not given', () => {
    const wrapper = mount(DisplayHeading, { props: { title: 'A' } });

    expect(wrapper.find('[data-slot="eyebrow-label"]').exists()).toBe(false);
    expect(wrapper.find('p').exists()).toBe(false);
  });

  it('renders eyebrow and lead when given', () => {
    const wrapper = mount(DisplayHeading, {
      props: { title: 'A', eyebrow: 'Naujienos', lead: 'Aprašymas.' },
    });

    expect(wrapper.get('[data-slot="eyebrow-label"]').text()).toBe('Naujienos');
    expect(wrapper.text()).toContain('Aprašymas.');
  });

  it('lets a slot override the matching prop', () => {
    const wrapper = mount(DisplayHeading, {
      props: { title: 'From prop', eyebrow: 'Prop eyebrow' },
      slots: { default: 'From slot', eyebrow: 'Slot eyebrow' },
    });

    expect(wrapper.get('h2').text()).toBe('From slot');
    expect(wrapper.get('[data-slot="eyebrow-label"]').text()).toBe('Slot eyebrow');
  });

  it('steps the visual size independently of the heading level', () => {
    const small = mount(DisplayHeading, { props: { title: 'A', as: 'h1', size: 'sm' } });
    const large = mount(DisplayHeading, { props: { title: 'A', as: 'h1', size: 'xl' } });

    expect(small.get('h1').classes()).toContain('text-2xl');
    expect(large.get('h1').classes()).toContain('text-5xl');
  });
});

describe('SectionBand', () => {
  it('wraps content in the shared measure by default', () => {
    const wrapper = mount(SectionBand, { slots: { default: '<p>x</p>' } });

    expect(wrapper.get('div').classes()).toContain('max-w-7xl');
  });

  it('drops the measure when bleeding edge to edge', () => {
    const wrapper = mount(SectionBand, { props: { bleed: true }, slots: { default: '<p>x</p>' } });

    expect(wrapper.get('div').classes()).not.toContain('max-w-7xl');
  });

  it('places the hairline on the requested side', () => {
    expect(mount(SectionBand, { props: { divider: 'top' } }).classes()).toContain('border-t');
    expect(mount(SectionBand, { props: { divider: 'bottom' } }).classes()).toContain('border-b');
    expect(mount(SectionBand).classes()).not.toContain('border-t');
  });
});

describe('TagChip', () => {
  it('renders a span by default and an anchor when linked', () => {
    expect(mount(TagChip, { props: { label: 'Gidai' } }).element.tagName).toBe('SPAN');

    const linked = mount(TagChip, { props: { label: 'Gidai', href: '/naujienos' } });
    expect(linked.element.tagName).toBe('A');
    expect(linked.attributes('href')).toBe('/naujienos');
  });

  it('uses the brand fill for the solid variant only', () => {
    expect(mount(TagChip, { props: { label: 'A' } }).classes()).toContain('bg-brand-fill');
    expect(mount(TagChip, { props: { label: 'A', variant: 'muted' } }).classes())
      .not.toContain('bg-brand-fill');
  });
});
