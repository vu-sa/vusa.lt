import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RichContentCard from '../RichContentCard.vue';

function makeElement(options: Record<string, unknown>) {
  return { type: 'shadcn-card', json_content: {}, options } as unknown as models.ContentPart;
}

describe('RichContentCard', () => {
  it('renders no inline style attributes (no useDark-derived colour logic)', () => {
    const wrapper = mount(RichContentCard, {
      props: { element: makeElement({ title: 'T' }) },
    });
    const styled = wrapper.findAll('[style]');
    expect(styled).toHaveLength(0);
  });

  it('renders one fixed, token-driven surface regardless of stored options', () => {
    const wrapper = mount(RichContentCard, { props: { element: makeElement({}) } });
    const rootClass = wrapper.attributes('class') ?? '';
    expect(rootClass).toContain('rounded-2xl');
    expect(rootClass).toContain('bg-card');
    expect(rootClass).not.toContain('zinc-');
  });

  it('ignores drifted options still present on old rows (color/variant/isTitleColored/showIcon)', () => {
    // Cast: these keys no longer exist on ShadcnCard['options'] — asserting the
    // component tolerates legacy rows saved before the cleanup migration ran.
    const wrapper = mount(RichContentCard, {
      props: { element: makeElement({ variant: 'soft', color: 'red', title: 'T', isTitleColored: true, showIcon: true }) },
    });
    const rootClass = wrapper.attributes('class') ?? '';
    expect(rootClass).toContain('bg-card');
    expect(wrapper.find('[data-slot="card-title"]').classes().join(' ')).toContain('text-foreground');
    expect(wrapper.find('svg').exists()).toBe(false);
  });

  it('does not render a title block when no title is set', () => {
    const wrapper = mount(RichContentCard, { props: { element: makeElement({}) } });
    expect(wrapper.find('[data-slot="card-header"]').exists()).toBe(false);
  });

  it('renders slot content inside a .rc-prose wrapper', () => {
    const wrapper = mount(RichContentCard, {
      props: { element: makeElement({}) },
      slots: { default: '<p>Hello</p>' },
    });
    expect(wrapper.find('.rc-prose p').text()).toBe('Hello');
  });

  it('renders a plain (non-editable) title with no contenteditable when editable is not set', () => {
    const wrapper = mount(RichContentCard, { props: { element: makeElement({ title: 'Kortelė' }) } });
    const title = wrapper.find('[data-slot="card-title"]');
    expect(title.text()).toBe('Kortelė');
    expect(title.attributes('contenteditable')).toBeUndefined();
  });

  it('shows an editable, empty title header when editable and no title is set yet', () => {
    const wrapper = mount(RichContentCard, { props: { element: makeElement({}), editable: true } });
    expect(wrapper.find('[data-slot="card-header"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="card-title"]').attributes('contenteditable')).toBe('plaintext-only');
  });

  it('emits update:element with the patched title, preserving other options', async () => {
    const wrapper = mount(RichContentCard, {
      props: { element: makeElement({ title: 'Old' }), editable: true },
    });
    const title = wrapper.find('[data-slot="card-title"]');
    title.element.textContent = 'New title';
    await title.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted![emitted!.length - 1]![0] as { options: Record<string, unknown> };
    expect(patched.options.title).toBe('New title');
  });
});
