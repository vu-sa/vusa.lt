import { afterEach, describe, expect, it } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { Editor } from '@tiptap/core';
import { markRaw } from 'vue';

import TiptapFormattingButtons from '../TiptapFormattingButtons.vue';
import TiptapListButtons from '../TiptapListButtons.vue';
import TiptapLinkPopover from '../TiptapLinkPopover.vue';
import { createCommentExtensions, createMarksExtensions } from '../extensions/presets';

import { commonStubs } from '@/tests/stubs';

const editors: Editor[] = [];
const wrappers: ReturnType<typeof mount>[] = [];

function makeEditor(content = '<p>Original label</p>', marksOnly = false) {
  const editor = markRaw(new Editor({ extensions: marksOnly ? createMarksExtensions() : createCommentExtensions(), content }));
  editors.push(editor);
  return editor;
}

afterEach(() => {
  wrappers.splice(0).forEach(wrapper => wrapper.unmount());
  editors.splice(0).forEach(editor => editor.destroy());
});

describe('shared comment controls', () => {
  it('shows only registered marks and lists, preserving the default marks selection', () => {
    const editor = makeEditor('', true);
    const formatting = mount(TiptapFormattingButtons, { props: { editor, marks: ['bold', 'italic', 'underline', 'strike'] } });
    const lists = mount(TiptapListButtons, { props: { editor } });
    wrappers.push(formatting, lists);

    expect(formatting.findAll('[data-slot="tiptap-tool"]')).toHaveLength(3);
    expect(formatting.find('[data-testid="tiptap-format-strike"]').exists()).toBe(false);
    expect(lists.findAll('button')).toHaveLength(0);
  });

  it('formats a selection and converts it to a list without submitting a surrounding form', async () => {
    const editor = makeEditor();
    const formatting = mount(TiptapFormattingButtons, { props: { editor, marks: ['bold', 'strike'] } });
    const lists = mount(TiptapListButtons, { props: { editor } });
    wrappers.push(formatting, lists);
    editor.commands.selectAll();

    await formatting.find('[data-testid="tiptap-format-strike"]').trigger('click');
    await lists.find('[data-testid="tiptap-bulletList"]').trigger('click');

    expect(editor.getHTML()).toContain('<ul>');
    expect(editor.getHTML()).toContain('<s>Original label</s>');
    for (const button of [...formatting.findAll('button'), ...lists.findAll('button')]) {
      expect(button.attributes('type')).toBe('button');
      expect(button.attributes('aria-label')).toBeTruthy();
    }
  });

  it('edits the selected link label and removes the link through the compact form', async () => {
    const editor = makeEditor('<p><a href="https://old.example">Original label</a></p>');
    editor.commands.setTextSelection(4);
    const wrapper = mount(TiptapLinkPopover, {
      props: { editor },
      global: { stubs: { ...commonStubs, Popover: { template: '<div><slot /></div>' }, PopoverContent: { template: '<div><slot /></div>' }, PopoverTrigger: { template: '<div><slot /></div>' } } },
    });
    wrappers.push(wrapper);
    await wrapper.find('[data-testid="tiptap-link"]').trigger('click');
    // The stub does not implement Popover's open event.
    (wrapper.vm as unknown as { open: () => void }).open();
    await flushPromises();

    await wrapper.find('[data-testid="link-url"]').setValue('/mano/meetings');
    await wrapper.find('[data-testid="link-text"]').setValue('Meeting');
    await wrapper.find('[data-testid="link-url"]').trigger('keydown', { key: 'Enter' });

    expect(editor.getText()).toBe('Meeting');
    expect(editor.getHTML()).toContain('href="/mano/meetings"');
    expect(editor.getHTML()).not.toContain('Original label');

    editor.commands.setTextSelection(2);
    (wrapper.vm as unknown as { open: () => void }).open();
    await flushPromises();
    await wrapper.findAll('button').find(button => button.text() === 'rich-content.remove_link')!.trigger('click');

    expect(editor.getHTML()).not.toContain('<a');
    expect(editor.getText()).toBe('Meeting');
  });
});
