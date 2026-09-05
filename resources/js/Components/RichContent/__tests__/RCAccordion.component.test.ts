import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCAccordion from '../RCAccordion.vue';

import type { ShadcnAccordion } from '@/Types/contentParts';

function emptyDoc(): ShadcnAccordion['json_content'][number]['content'] {
  return { type: 'doc', content: [] } as ShadcnAccordion['json_content'][number]['content'];
}

function makeElement(items: Partial<ShadcnAccordion['json_content'][number]>[] = []): ShadcnAccordion {
  return {
    json_content: items.map(item => ({ label: '', content: emptyDoc(), ...item })),
    options: {},
  };
}

const TiptapEditorStub = {
  props: ['modelValue', 'preset', 'proseStyle', 'placeholder'],
  emits: ['update:modelValue'],
  template: '<div class="tiptap-editor-stub" />',
};

const stubs = { TiptapEditor: TiptapEditorStub };

describe('RCAccordion — public (non-editable)', () => {
  it('renders each item label as plain, non-editable text', () => {
    const wrapper = mount(RCAccordion, {
      props: { element: makeElement([{ label: 'Klausimas' }]) },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Klausimas');
    expect(wrapper.find('[contenteditable]').exists()).toBe(false);
  });

  it('renders no add/remove controls', () => {
    const wrapper = mount(RCAccordion, {
      props: { element: makeElement([{ label: 'A' }, { label: 'B' }]) },
      global: { stubs },
    });
    expect(wrapper.find('[data-rc-accordion-remove-item]').exists()).toBe(false);
    expect(wrapper.find('[data-rc-accordion-add-item]').exists()).toBe(false);
    expect(wrapper.findAll('button')).toHaveLength(2); // the two collapsible triggers only
  });
});

describe('RCAccordion — editable (full-screen editor)', () => {
  function mountEditable(element: ShadcnAccordion, activeInlineField: string | null = null) {
    return mount(RCAccordion, {
      props: { element, editable: true, blockKey: 'acc-1', activeInlineField },
      global: { stubs },
    });
  }

  it('starts every item collapsed, same as the public accordion', () => {
    const wrapper = mountEditable(makeElement([{ label: 'A' }, { label: 'B' }]));
    expect(wrapper.findAll('[data-rc-accordion-content]')).toHaveLength(0);
  });

  it('opens on clicking the row, using the accordion’s own trigger — no bespoke control', async () => {
    const wrapper = mountEditable(makeElement([{ label: 'A' }, { label: 'B' }]));
    const triggers = wrapper.findAll('[data-slot="accordion-trigger"]');
    await triggers[0]!.trigger('click');

    expect(wrapper.findAll('[data-rc-accordion-content]')).toHaveLength(1);

    // Opening one item doesn't affect its sibling.
    await triggers[1]!.trigger('click');
    expect(wrapper.findAll('[data-rc-accordion-content]')).toHaveLength(2);

    // Toggling the first one closed again leaves the second open.
    await triggers[0]!.trigger('click');
    expect(wrapper.findAll('[data-rc-accordion-content]')).toHaveLength(1);
  });

  it('renders the trigger as a plain div, not a nested button, and the label inline-editable', () => {
    const wrapper = mountEditable(makeElement([{ label: 'Klausimas' }]));
    expect(wrapper.find('button [contenteditable]').exists()).toBe(false);
    const label = wrapper.find('[contenteditable]');
    expect(label.exists()).toBe(true);
    expect(label.text()).toBe('Klausimas');
  });

  it('emits update:element with the patched label, preserving content', async () => {
    const wrapper = mountEditable(makeElement([{ label: 'Old' }]));
    const label = wrapper.find('[contenteditable]');
    label.element.textContent = 'New label';
    await label.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as ShadcnAccordion;
    expect(patched.json_content[0]?.label).toBe('New label');
  });

  it('shows a placeholder for an opened item with no content yet', async () => {
    const wrapper = mountEditable(makeElement([{ label: 'A' }]));
    await wrapper.get('[data-slot="accordion-trigger"]').trigger('click');

    const contentArea = wrapper.find('[data-rc-accordion-content]');
    expect(contentArea.text()).toContain('rich-content.content'); // $t is untranslated in tests
  });

  it('clicking an unclaimed content area emits claim-inline-field with the item id', async () => {
    const wrapper = mountEditable(makeElement([{ label: 'A' }, { label: 'B' }]));
    const triggers = wrapper.findAll('[data-slot="accordion-trigger"]');
    await triggers[1]!.trigger('click');

    const contentArea = wrapper.get('[data-rc-accordion-content]');
    await contentArea.trigger('click');

    expect(wrapper.emitted('claim-inline-field')?.at(-1)).toEqual(['acc-1:content:1']);
  });

  it('mounts a live TiptapEditor for the claimed, open item, and bubbles its edits', async () => {
    const wrapper = mountEditable(makeElement([{ label: 'A' }, { label: 'B' }]), 'acc-1:content:1');
    // A field is only ever claimed after its item is opened (see the click-to-open-then-
    // claim test above), so mounting pre-claimed still requires opening it here too —
    // `unmountOnHide` (the accordion's default) keeps a collapsed item's content out of
    // the DOM regardless of which field is claimed.
    await wrapper.findAll('[data-slot="accordion-trigger"]')[1]!.trigger('click');
    expect(wrapper.findAll('.tiptap-editor-stub')).toHaveLength(1);

    await wrapper.findComponent(TiptapEditorStub).vm.$emit('update:modelValue', { type: 'doc', content: [{ type: 'paragraph' }] });

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as ShadcnAccordion;
    expect(patched.json_content[1]?.content).toEqual({ type: 'doc', content: [{ type: 'paragraph' }] });
    expect(patched.json_content[0]?.label).toBe('A'); // untouched sibling item
  });

  it('adds a new empty item, opens it, and immediately claims its content field', async () => {
    const wrapper = mountEditable(makeElement([{ label: 'A' }]));
    await wrapper.find('[data-rc-accordion-add-item]').trigger('click');

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as ShadcnAccordion;
    expect(patched.json_content).toHaveLength(2);
    expect(patched.json_content[1]?.label).toBe('');

    expect(wrapper.emitted('claim-inline-field')?.at(-1)).toEqual(['acc-1:content:1']);
  });

  it('removes an item, emits update:element without it, and releases any live claim', async () => {
    const wrapper = mountEditable(makeElement([{ label: 'A' }, { label: 'B' }]));
    const removeButtons = wrapper.findAll('[data-rc-accordion-remove-item]');
    expect(removeButtons).toHaveLength(2);
    await removeButtons[0]!.trigger('click');

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as ShadcnAccordion;
    expect(patched.json_content.map(item => item.label)).toEqual(['B']);
    expect(wrapper.emitted('claim-inline-field')?.at(-1)).toEqual([null]);
  });

  it('places the remove button beside each item, not inside its card', () => {
    const wrapper = mountEditable(makeElement([{ label: 'A' }]));
    const removeButton = wrapper.get('[data-rc-accordion-remove-item]');
    const card = wrapper.get('[data-slot="accordion-trigger"]').element.closest('[class*="rounded-lg"]');
    expect(card?.contains(removeButton.element)).toBe(false);
  });
});
