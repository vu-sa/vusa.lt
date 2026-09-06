import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import type { Editor } from '@tiptap/vue-3';

import RCSmartTiptapToolbar from '../RCSmartTiptapToolbar.vue';

function makeMockEditor(overrides = {}): Editor {
  return {
    isFocused: true,
    isDestroyed: false,
    isActive: vi.fn(() => false),
    can: () => ({
      chain: () => ({
        focus: () => ({
          undo: () => ({ run: vi.fn() }),
          redo: () => ({ run: vi.fn() }),
        }),
      }),
    }),
    chain: () => ({
      focus: () => ({
        setParagraph: () => ({ run: vi.fn() }),
        toggleHeading: () => ({ run: vi.fn() }),
        toggleBulletList: () => ({ run: vi.fn() }),
        toggleOrderedList: () => ({ run: vi.fn() }),
        toggleBlockquote: () => ({ run: vi.fn() }),
        insertTable: () => ({ run: vi.fn() }),
        undo: () => ({ run: vi.fn() }),
        redo: () => ({ run: vi.fn() }),
      }),
    }),
    state: {
      selection: {
        $from: { depth: 1, before: () => 0, pos: 0 },
      },
    },
    view: {
      nodeDOM: () => {
        const el = document.createElement('p');
        el.getBoundingClientRect = () => ({
          top: 150,
          bottom: 180,
          left: 100,
          right: 300,
          width: 200,
          height: 30,
          x: 100,
          y: 150,
          toJSON: () => {},
        });
        return el;
      },
    },
    on: vi.fn(),
    off: vi.fn(),
    ...overrides,
  } as unknown as Editor;
}

describe('RCSmartTiptapToolbar', () => {
  it('renders smart toolbar controls when focused', () => {
    const editor = makeMockEditor();
    const wrapper = mount(RCSmartTiptapToolbar, {
      props: { editor },
      global: {
        stubs: {
          TiptapFormattingButtons: true,
          TiptapLinkButton: true,
          TiptapImageButton: true,
        },
      },
    });

    expect(wrapper.find('[data-rc-smart-toolbar]').exists()).toBe(true);
    expect(wrapper.findAll('button').length).toBeGreaterThan(0);
  });

  it('minimizes into pill trigger when close button is clicked', async () => {
    const editor = makeMockEditor();
    const wrapper = mount(RCSmartTiptapToolbar, {
      props: { editor },
      global: {
        stubs: {
          TiptapFormattingButtons: true,
          TiptapLinkButton: true,
          TiptapImageButton: true,
        },
      },
    });

    const closeBtn = wrapper.findAll('button').find(b => b.attributes('title')?.includes('rich-content.close_toolbar'));
    expect(closeBtn).toBeDefined();
    await closeBtn!.trigger('click');

    expect(wrapper.find('[data-rc-smart-toolbar]').exists()).toBe(false);
    expect(wrapper.text()).toContain('rich-content.show_toolbar');
  });
});
