import { describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import { mount } from '@vue/test-utils';

const darkRef = ref(false);
vi.mock('@vueuse/core', async (importOriginal) => {
  const actual = await importOriginal() as Record<string, unknown>;
  return { ...actual, useDark: () => darkRef };
});

import RCSideBySideDialog from '../RCSideBySideDialog.vue';

import { commonStubs } from '@/tests/stubs';

const ContentEditorFactoryStub = {
  props: ['content'],
  template: '<div class="editor-stub">{{ content?.type }}</div>',
};

const BlockPreviewRendererStub = {
  props: ['element'],
  template: '<div class="preview-stub" :data-width="element?.options?.width">preview</div>',
};

function makeContent() {
  return {
    id: 1,
    type: 'shadcn-card',
    json_content: {},
    options: { width: 'content' },
  };
}

function mountDialog(open = true) {
  return mount(RCSideBySideDialog, {
    props: { open, content: makeContent() },
    global: {
      stubs: { ...commonStubs, ContentEditorFactory: ContentEditorFactoryStub, BlockPreviewRenderer: BlockPreviewRendererStub },
    },
  });
}

describe('RCSideBySideDialog', () => {
  it('does not mount the editor/preview panes while closed', () => {
    const wrapper = mountDialog(false);
    expect(wrapper.find('.editor-stub').exists()).toBe(false);
    expect(wrapper.find('.preview-stub').exists()).toBe(false);
  });

  it('mounts both panes while open, defaulting the preview width to the block\'s actual width', () => {
    const wrapper = mountDialog(true);
    expect(wrapper.find('.editor-stub').exists()).toBe(true);
    expect(wrapper.find('.preview-stub').attributes('data-width')).toBe('content');
  });

  it('changing the preview width updates the preview but never the original content object', async () => {
    const content = makeContent();
    const wrapper = mount(RCSideBySideDialog, {
      props: { open: true, content },
      global: {
        stubs: { ...commonStubs, ContentEditorFactory: ContentEditorFactoryStub, BlockPreviewRenderer: BlockPreviewRendererStub },
      },
    });

    const fullOption = wrapper.findAll('button').find(el => el.text().includes('rich-content.width_full'));
    expect(fullOption, 'the "full" width option should be findable in the picker dropdown').toBeTruthy();
    await fullOption!.trigger('click');

    expect(wrapper.find('.preview-stub').attributes('data-width')).toBe('full');
    // The real content object must never be touched by the preview-only width picker.
    expect(content.options.width).toBe('content');
  });

  it('toggles dark mode through the global useDark() ref (no local .dark scoping)', async () => {
    darkRef.value = false;
    const wrapper = mountDialog(true);

    // No local `.dark` is applied to the preview pane wrapper anymore — the toggle
    // drives the admin theme itself via useDark() (verified below), and the actual
    // visual rendering (Tailwind's `dark:` matching a `.dark` ancestor) is intentionally
    // not asserted here: it needs a real browser + CSS pipeline, which jsdom can't model.
    expect(wrapper.find('.dark').exists()).toBe(false);

    const toggle = wrapper.find('button[title="rich-content.preview_dark_mode"]');
    expect(toggle.exists()).toBe(true);
    await toggle.trigger('click');

    expect(darkRef.value).toBe(true);
  });
});
