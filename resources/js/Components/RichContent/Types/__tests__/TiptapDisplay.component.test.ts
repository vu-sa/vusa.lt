import { nextTick } from 'vue';
import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import TiptapDisplay from '../TiptapDisplay.vue';

function makeElement(html: string | null = '<p>Test paragraph</p>', json_content: Record<string, unknown> | null = {}) {
  return {
    type: 'tiptap',
    html,
    json_content,
  };
}

describe('TiptapDisplay', () => {
  it('renders server-rendered html when editable is false', () => {
    const wrapper = mount(TiptapDisplay, {
      props: {
        element: makeElement('<p>Hello world</p>'),
      },
    });

    expect(wrapper.text()).toContain('Hello world');
    expect(wrapper.find('.rc-prose').exists()).toBe(true);
  });

  it('renders fallback error text when html is null', () => {
    const wrapper = mount(TiptapDisplay, {
      props: {
        element: makeElement(null, null),
      },
    });

    expect(wrapper.text()).toContain('Turinio nepavyko atvaizduoti');
  });

  it('renders smart toolbar and editor when editable is true', async () => {
    const wrapper = mount(TiptapDisplay, {
      props: {
        element: makeElement('<p>Editable content</p>', {
          type: 'doc',
          content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Editable content' }] }],
        }),
        editable: true,
        blockKey: 'tip-1',
      },
      global: {
        stubs: {
          RCSmartTiptapToolbar: { template: '<div class="smart-toolbar-mock" />' },
          BubbleMenu: true,
        },
      },
    });

    await nextTick();
    await flushPromises();

    expect(wrapper.find('.smart-toolbar-mock').exists()).toBe(true);
    expect(wrapper.text()).toContain('Editable content');
  });

  /**
   * The editing surface must be the ProseMirror root itself, not a wrapper: the
   * shared prose block's flow rules are direct-child selectors (`> * + *`, `> h2`),
   * and EditorContent mounts the root two levels below any wrapper — on a wrapper
   * the published flow/heading scale never reaches the content and the editor
   * falls back to tiptap-base.css's compact chrome (the edit ≠ preview drift).
   * Same pattern as TiptapEditor.vue's `prose-style`.
   */
  it('mounts the prose editing surface on the ProseMirror root, not a wrapper', async () => {
    const wrapper = mount(TiptapDisplay, {
      props: {
        element: makeElement('<p>Editable content</p>', {
          type: 'doc',
          content: [{ type: 'paragraph', content: [{ type: 'text', text: 'Editable content' }] }],
        }),
        editable: true,
        blockKey: 'tip-1',
      },
      global: {
        stubs: {
          RCSmartTiptapToolbar: { template: '<div class="smart-toolbar-mock" />' },
          BubbleMenu: true,
        },
      },
    });

    await nextTick();
    await flushPromises();

    const proseMirror = wrapper.find('.tiptap.ProseMirror');
    expect(proseMirror.exists()).toBe(true);
    expect(proseMirror.classes()).toContain('rc-prose-editing');
    // A wrapper holding the class would leave the > * flow rules matching the
    // EditorContent div instead of the paragraphs.
    expect(wrapper.find('.rc-prose:not(.ProseMirror)').exists()).toBe(false);
  });
});
