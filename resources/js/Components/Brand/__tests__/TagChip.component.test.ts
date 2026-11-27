import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import TagChip from '../TagChip.vue';

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
  it('renders label or slot text', () => {
    const fromProp = mount(TagChip, { props: { label: 'Atstovavimas' } });
    expect(fromProp.text()).toContain('Atstovavimas');

    const fromSlot = mount(TagChip, {
      slots: { default: 'Renginiai' },
    });
    expect(fromSlot.text()).toContain('Renginiai');
  });

  it('applies variant classes correctly', () => {
    const solid = mount(TagChip, { props: { label: 'Solid', variant: 'solid' } });
    expect(solid.html()).toContain('bg-brand-fill');

    const outline = mount(TagChip, { props: { label: 'Outline', variant: 'outline' } });
    expect(outline.html()).toContain('border-brand');

    const muted = mount(TagChip, { props: { label: 'Muted', variant: 'muted' } });
    expect(muted.html()).toContain('border-border');
  });

  it('renders a remove button and emits remove when removable is true', async () => {
    const wrapper = mount(TagChip, {
      props: { label: 'VU SA MIF', removable: true },
    });

    const button = wrapper.find('button');
    expect(button.exists()).toBe(true);

    await button.trigger('click');
    expect(wrapper.emitted('remove')).toHaveLength(1);
  });

  it('does not render a remove button by default', () => {
    const wrapper = mount(TagChip, {
      props: { label: 'VU SA MIF' },
    });

    expect(wrapper.find('button').exists()).toBe(false);
  });
});
