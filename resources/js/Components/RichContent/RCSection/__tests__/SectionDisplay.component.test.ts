import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import SectionDisplay from '../SectionDisplay.vue';

import type { Section } from '@/Types/contentParts';

function makeElement(options: Partial<Section['options']> = {}): Section {
  return {
    json_content: {},
    options: { inner: 'full', wraps: 'following', ...options },
  };
}

describe('SectionDisplay — public (non-editable)', () => {
  it('renders the header when a title is set', () => {
    const wrapper = mount(SectionDisplay, { props: { element: makeElement({ title: 'Nariams' }) } });
    expect(wrapper.text()).toContain('Nariams');
    expect(wrapper.find('h2').exists()).toBe(true);
  });

  it('renders nothing header-related when there is no title', () => {
    const wrapper = mount(SectionDisplay, { props: { element: makeElement() } });
    expect(wrapper.find('h2').exists()).toBe(false);
    expect(wrapper.find('h3').exists()).toBe(false);
    expect(wrapper.find('h4').exists()).toBe(false);
  });

  it('renders no contenteditable fields', () => {
    const wrapper = mount(SectionDisplay, { props: { element: makeElement({ title: 'Nariams' }) } });
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
  });
});

describe('SectionDisplay — editable (full-screen editor)', () => {
  it('renders the header even when every field is empty, so there is always something to click', () => {
    const wrapper = mount(SectionDisplay, { props: { element: makeElement(), editable: true } });
    expect(wrapper.findAll('[contenteditable]')).toHaveLength(3); // eyebrow, title, subtitle
  });

  it('renders the title at the configured heading level', () => {
    const wrapper = mount(SectionDisplay, { props: { element: makeElement({ title: 'Nariams', headingLevel: 3 }), editable: true } });
    expect(wrapper.find('h3[contenteditable]').exists()).toBe(true);
    expect(wrapper.find('h2').exists()).toBe(false);
  });

  it('emits update:element with the patched title, preserving other options', async () => {
    const wrapper = mount(SectionDisplay, { props: { element: makeElement({ title: 'Old', subtitle: 'Sub' }), editable: true } });
    const title = wrapper.find('h2[contenteditable]');
    title.element.textContent = 'New title';
    await title.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as Section;
    expect(patched.options.title).toBe('New title');
    expect(patched.options.subtitle).toBe('Sub');
  });

  it('emits update:element with the patched eyebrow', async () => {
    const wrapper = mount(SectionDisplay, { props: { element: makeElement(), editable: true } });
    const [eyebrow] = wrapper.findAll('[contenteditable]');
    eyebrow!.element.textContent = 'Naujiena';
    await eyebrow!.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200));

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as Section;
    expect(patched.options.eyebrow).toBe('Naujiena');
  });

  it('does not leak blockKey/activeInlineField as DOM attributes on the root', () => {
    const wrapper = mount(SectionDisplay, {
      props: { element: makeElement({ title: 'Nariams' }), editable: true, blockKey: 'sec-1', activeInlineField: null },
    });
    expect(wrapper.attributes('block-key')).toBeUndefined();
    expect(wrapper.attributes('active-inline-field')).toBeUndefined();
  });

  it('still renders wrapped children through the default slot', () => {
    const wrapper = mount(SectionDisplay, {
      props: { element: makeElement({ title: 'Nariams' }), editable: true, hasChildren: true },
      slots: { default: '<p class="child">Child block</p>' },
    });
    expect(wrapper.find('.child').exists()).toBe(true);
  });
});
