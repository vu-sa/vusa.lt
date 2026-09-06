import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import ContentGridDisplay from '../ContentGridDisplay.vue';

import type { ContentGrid } from '@/Types/contentParts';

const TiptapEditorStub = {
  props: ['modelValue', 'preset', 'proseStyle', 'placeholder'],
  emits: ['update:modelValue'],
  template: '<div class="tiptap-editor-stub" />',
};

const RCImageHotspotStub = {
  props: ['imageUrl', 'alt', 'objectPosition', 'blockKey', 'imageIndex', 'fullTileTrigger'],
  emits: ['update:image', 'update:alt', 'update:object-position', 'delete'],
  template: '<div class="image-hotspot-stub"><slot name="options" /></div>',
};

const RCGridColumnOptionsStub = {
  props: ['blockKey', 'rowIndex', 'colIndex', 'type', 'width', 'canMoveLeft', 'canMoveRight', 'canRemove'],
  emits: ['update:type', 'update:width', 'move-left', 'move-right', 'remove'],
  template: '<div class="column-options-stub" />',
};

const RCGridRowOptionsStub = {
  props: ['blockKey', 'rowIndex', 'canMoveUp', 'canMoveDown', 'canRemove'],
  emits: ['move-up', 'move-down', 'remove'],
  template: '<div class="row-options-stub" />',
};

const stubs = {
  TiptapEditor: TiptapEditorStub,
  RCImageHotspot: RCImageHotspotStub,
  RCGridColumnOptions: RCGridColumnOptionsStub,
  RCGridRowOptions: RCGridRowOptionsStub,
};

function makeElement(rows: ContentGrid['json_content']): { json_content: ContentGrid['json_content']; options: ContentGrid['options'] } {
  return { json_content: rows, options: { gap: 'gap-4', mobileStacking: true, equalHeight: false } };
}

function mountEditable(rows: ContentGrid['json_content'], activeInlineField: string | null = null) {
  return mount(ContentGridDisplay, {
    props: { element: makeElement(rows), editable: true, blockKey: 'grid-1', activeInlineField },
    global: { stubs },
  });
}

describe('ContentGridDisplay — editable (full-screen editor)', () => {
  it('shows a placeholder for an unclaimed tiptap cell and claims it on click', async () => {
    const wrapper = mountEditable([{ columns: [{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }] }]);

    const cell = wrapper.get('[data-rc-grid-cell-content]');
    expect(cell.text()).toContain('rich-content.content');

    await cell.trigger('click');
    expect(wrapper.emitted('claim-inline-field')).toEqual([['grid-1:cell:0:0']]);
  });

  it('mounts a live TiptapEditor only for the claimed cell, and bubbles edits to that cell only', async () => {
    const wrapper = mountEditable([
      {
        columns: [
          { width: 'col-span-6', content: { type: 'tiptap', value: { type: 'doc', content: [] } } },
          { width: 'col-span-6', content: { type: 'tiptap', value: { type: 'doc', content: [] } } },
        ],
      },
    ], 'grid-1:cell:0:1');

    expect(wrapper.findAll('.tiptap-editor-stub')).toHaveLength(1);

    await wrapper.findComponent(TiptapEditorStub).vm.$emit('update:modelValue', { type: 'doc', content: [{ type: 'paragraph' }] });

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content[0]?.columns[1]?.content.value).toEqual({ type: 'doc', content: [{ type: 'paragraph' }] });
    expect(patched.json_content[0]?.columns[0]?.content.value).toEqual({ type: 'doc', content: [] }); // untouched sibling column
  });

  it('replacing an image cell\'s image patches only that column', async () => {
    const wrapper = mountEditable([
      {
        columns: [
          { width: 'col-span-6', content: { type: 'image', value: '/a.jpg' } },
          { width: 'col-span-6', content: { type: 'image', value: '/b.jpg' } },
        ],
      },
    ]);

    const hotspots = wrapper.findAllComponents(RCImageHotspotStub);
    expect(hotspots).toHaveLength(2);
    await hotspots[0]!.vm.$emit('update:image', { src: '/new.jpg', alt: 'New', title: 'New title' });

    const patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content[0]?.columns[0]?.content.value).toBe('/new.jpg');
    expect(patched.json_content[0]?.columns[0]?.content.alt).toBe('New');
    expect(patched.json_content[0]?.columns[1]?.content.value).toBe('/b.jpg'); // untouched sibling column
  });

  it('clearing an image cell empties its value without removing the column', async () => {
    const wrapper = mountEditable([{ columns: [{ width: 'col-span-12', content: { type: 'image', value: '/a.jpg' } }] }]);

    await wrapper.findComponent(RCImageHotspotStub).vm.$emit('delete');

    const patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content[0]?.columns).toHaveLength(1);
    expect(patched.json_content[0]?.columns[0]?.content.value).toBe('');
  });

  it('edits a card\'s title inline via RCInlineText, preserving the rest of the card', async () => {
    const wrapper = mountEditable([
      { columns: [{ width: 'col-span-12', content: { type: 'card', value: { title: 'Old', description: 'Old desc' } } }] },
    ]);

    const title = wrapper.find('[contenteditable]');
    title.element.textContent = 'New title';
    await title.trigger('input');
    await new Promise(resolve => setTimeout(resolve, 200)); // RCInlineText's debounce

    const patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    const value = patched.json_content[0]?.columns[0]?.content.value as { title?: string; description?: string };
    expect(value.title).toBe('New title');
    expect(value.description).toBe('Old desc');
  });

  it('edits a card\'s link URL through the image hotspot\'s options slot', async () => {
    const wrapper = mountEditable([
      { columns: [{ width: 'col-span-12', content: { type: 'card', value: { title: 'T', href: '' } } }] },
    ]);

    const hrefInput = wrapper.get('input[type="url"]');
    await hrefInput.setValue('https://example.org');

    const patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    const value = patched.json_content[0]?.columns[0]?.content.value as { href?: string };
    expect(value.href).toBe('https://example.org');
  });

  it('adding a row appends a full-width tiptap row and claims its first cell', async () => {
    const wrapper = mountEditable([{ columns: [{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }] }]);

    await wrapper.get('[data-rc-grid-add-row]').trigger('click');

    const emitted = wrapper.emitted('update:element');
    expect(emitted).toBeTruthy();
    const patched = emitted!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content).toHaveLength(2);
    expect(patched.json_content[1]?.columns).toEqual([{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }]);

    expect(wrapper.emitted('claim-inline-field')?.at(-1)).toEqual(['grid-1:cell:1:0']);
  });

  it('adding a column via the trailing placeholder redistributes widths evenly', async () => {
    const wrapper = mountEditable([{ columns: [{ width: 'col-span-6', content: { type: 'tiptap', value: {} } }, { width: 'col-span-6', content: { type: 'image', value: '/a.jpg' } }] }]);

    await wrapper.get('[data-rc-grid-add-column]').trigger('click');

    const patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    const { columns } = patched.json_content[0]!;
    expect(columns).toHaveLength(3);
    expect(columns.every(c => c.width === 'col-span-4')).toBe(true);
    expect(columns[2]!.content).toEqual({ type: 'tiptap', value: {} });
  });

  it('hides the add-column placeholder once the row reaches the 4-column cap', () => {
    const columns = Array.from({ length: 4 }, () => ({ width: 'col-span-3', content: { type: 'tiptap' as const, value: {} } }));
    const wrapper = mountEditable([{ columns }]);
    expect(wrapper.find('[data-rc-grid-add-column]').exists()).toBe(false);
  });

  it('forwards RCGridColumnOptions events to the matching column, not its siblings', async () => {
    const wrapper = mountEditable([
      {
        columns: [
          { width: 'col-span-6', content: { type: 'tiptap', value: {} } },
          { width: 'col-span-6', content: { type: 'image', value: '/a.jpg' } },
        ],
      },
    ]);

    const columnOptions = wrapper.findAllComponents(RCGridColumnOptionsStub);
    expect(columnOptions).toHaveLength(2);
    expect(columnOptions[0]!.props('canMoveLeft')).toBe(false);
    expect(columnOptions[1]!.props('canMoveRight')).toBe(false);

    await columnOptions[1]!.vm.$emit('update:type', 'card');
    let patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content[0]?.columns[1]?.content).toEqual({
      type: 'card',
      value: { image: '', imageAlt: '', title: '', description: '', href: '' },
    });
    expect(patched.json_content[0]?.columns[0]?.content).toEqual({ type: 'tiptap', value: {} }); // untouched sibling

    await columnOptions[0]!.vm.$emit('update:width', 'col-span-4');
    patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content[0]?.columns[0]?.width).toBe('col-span-4');
  });

  it('moves and removes a column via RCGridColumnOptions, redistributing widths on removal', async () => {
    const wrapper = mountEditable([
      {
        columns: [
          { width: 'col-span-4', content: { type: 'tiptap', value: {} } },
          { width: 'col-span-4', content: { type: 'image', value: '/a.jpg' } },
          { width: 'col-span-4', content: { type: 'tiptap', value: {} } },
        ],
      },
    ]);

    const columnOptions = wrapper.findAllComponents(RCGridColumnOptionsStub);
    await columnOptions[0]!.vm.$emit('move-right');
    let patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content[0]?.columns[1]?.content.type).toBe('tiptap');
    expect(patched.json_content[0]?.columns[0]?.content.type).toBe('image');

    await columnOptions[2]!.vm.$emit('remove');
    patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    const { columns } = patched.json_content[0]!;
    expect(columns).toHaveLength(2);
    expect(columns.every(c => c.width === 'col-span-6')).toBe(true);
  });

  it('forwards RCGridRowOptions events to the matching row', async () => {
    const wrapper = mountEditable([
      { columns: [{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }] },
      { columns: [{ width: 'col-span-12', content: { type: 'image', value: '/a.jpg' } }] },
    ]);

    const rowOptions = wrapper.findAllComponents(RCGridRowOptionsStub);
    expect(rowOptions).toHaveLength(2);
    expect(rowOptions[0]!.props('canMoveUp')).toBe(false);
    expect(rowOptions[1]!.props('canMoveDown')).toBe(false);

    await rowOptions[0]!.vm.$emit('move-down');
    let patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content[0]?.columns[0]?.content.type).toBe('image');
    expect(patched.json_content[1]?.columns[0]?.content.type).toBe('tiptap');

    await rowOptions[1]!.vm.$emit('remove');
    patched = wrapper.emitted('update:element')!.at(-1)![0] as { json_content: ContentGrid['json_content'] };
    expect(patched.json_content).toHaveLength(1);
  });

  it('disables row removal when only one row remains', () => {
    const wrapper = mountEditable([{ columns: [{ width: 'col-span-12', content: { type: 'tiptap', value: {} } }] }]);
    const rowOptions = wrapper.findComponent(RCGridRowOptionsStub);
    expect(rowOptions.props('canRemove')).toBe(false);
  });
});
