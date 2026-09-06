import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import type { Editor } from '@tiptap/vue-3';
import { ref } from 'vue';

import RCSmartTiptapToolbar from '../RCSmartTiptapToolbar.vue';
import { SMART_TIPTAP_TOOLBAR_PORTAL_KEY } from '../smartToolbarPortal';

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

    expect(wrapper.find('[data-rc-smart-toolbar]').isVisible()).toBe(false);
    expect(wrapper.find('[data-rc-smart-toolbar-trigger]').isVisible()).toBe(true);
  });

  it('repositions when a nested fullscreen viewport scrolls', async () => {
    const scrollContainer = document.createElement('div');
    scrollContainer.style.overflowY = 'auto';
    const editorContainer = document.createElement('div');
    const editorContainerTop = 100;
    editorContainer.getBoundingClientRect = () => ({
      top: editorContainerTop,
      bottom: editorContainerTop + 200,
      left: 0,
      right: 500,
      width: 500,
      height: 200,
      x: 0,
      y: editorContainerTop,
      toJSON: () => {},
    });
    scrollContainer.append(editorContainer);
    document.body.append(scrollContainer);

    let paragraphTop = 150;
    const nodeDOM = vi.fn(() => {
      const element = document.createElement('p');
      element.getBoundingClientRect = () => ({
        top: paragraphTop,
        bottom: paragraphTop + 30,
        left: 100,
        right: 300,
        width: 200,
        height: 30,
        x: 100,
        y: paragraphTop,
        toJSON: () => {},
      });
      return element;
    });
    const editor = makeMockEditor({ view: { nodeDOM } });
    vi.stubGlobal('requestAnimationFrame', (callback: FrameRequestCallback) => {
      callback(0);
      return 1;
    });

    const wrapper = mount(RCSmartTiptapToolbar, {
      props: { editor, containerRef: editorContainer },
      global: {
        stubs: {
          TiptapFormattingButtons: true,
          TiptapLinkButton: true,
          TiptapImageButton: true,
        },
      },
    });

    nodeDOM.mockClear();
    const initialStyle = wrapper.find('[data-rc-smart-toolbar]').attributes('style');
    paragraphTop = 90;
    scrollContainer.dispatchEvent(new Event('scroll'));
    await wrapper.vm.$nextTick();

    expect(nodeDOM).toHaveBeenCalled();
    expect(wrapper.find('[data-rc-smart-toolbar]').attributes('style')).not.toBe(initialStyle);

    wrapper.unmount();
    scrollContainer.remove();
    vi.unstubAllGlobals();
  });

  it('renders into a provided dialog-owned portal', async () => {
    const portal = document.createElement('div');
    document.body.append(portal);

    const wrapper = mount(RCSmartTiptapToolbar, {
      props: { editor: makeMockEditor() },
      global: {
        provide: {
          [SMART_TIPTAP_TOOLBAR_PORTAL_KEY as symbol]: ref(portal),
        },
        stubs: {
          TiptapFormattingButtons: true,
          TiptapLinkButton: true,
          TiptapImageButton: true,
        },
      },
    });

    expect(portal.querySelector('[data-rc-smart-toolbar]')).not.toBeNull();

    (portal.querySelector('[title*="rich-content.close_toolbar"]') as HTMLButtonElement).click();
    await wrapper.vm.$nextTick();

    expect((portal.querySelector('[data-rc-smart-toolbar]') as HTMLElement).style.display).toBe('none');
    expect(portal.textContent).toContain('rich-content.show_toolbar');

    wrapper.unmount();
    portal.remove();
  });
});
