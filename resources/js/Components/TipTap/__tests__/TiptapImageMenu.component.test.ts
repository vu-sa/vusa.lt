import { describe, it, expect, vi } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';

import TiptapImageMenu from '../TiptapImageMenu.vue';
import { commonStubs } from '@/tests/stubs';

/**
 * BubbleMenu positions itself through floating-ui against a live ProseMirror view, so
 * it is stubbed to render its contents inline; the visibility rule it would drive is
 * covered separately in bubbleMenuVisibility.test.ts.
 */
const BubbleMenuStub = defineComponent({
  name: 'BubbleMenuStub',
  props: ['editor', 'shouldShow', 'pluginKey', 'options'],
  template: '<div data-testid="image-bubble"><slot /></div>',
});

function createMockEditor(attrs: Record<string, unknown> = {}) {
  const run = vi.fn();
  const chain = {
    focus: () => chain,
    deleteSelection: () => chain,
    updateAttributes: vi.fn(() => chain),
    run,
  };

  return {
    editor: {
      getAttributes: () => attrs,
      isActive: (name: string) => name === 'image',
      chain: () => chain,
    },
    chain,
    run,
  };
}

function mountMenu(attrs: Record<string, unknown> = {}) {
  const mock = createMockEditor(attrs);
  const wrapper = mount(TiptapImageMenu, {
    props: { editor: mock.editor as never },
    global: { stubs: { ...commonStubs, BubbleMenu: BubbleMenuStub } },
  });

  return { wrapper, ...mock };
}

function buttonByTitle(wrapper: ReturnType<typeof mount>, title: string) {
  return wrapper.findAll('button').find(button => button.attributes('title') === title);
}

describe('TiptapImageMenu', () => {
  it('offers alignment, sizing, alt text and removal next to the image', () => {
    const { wrapper } = mountMenu();

    for (const title of ['rich-content.align_left', 'rich-content.align_center', 'rich-content.align_right',
      'rich-content.image_size', 'rich-content.image_alt_text', 'rich-content.image_remove']) {
      expect(buttonByTitle(wrapper, title), title).toBeDefined();
    }
  });

  it('marks the image\'s current alignment as active', () => {
    const { wrapper } = mountMenu({ align: 'right' });

    expect(buttonByTitle(wrapper, 'rich-content.align_right')!.classes().join(' ')).toContain('bg-zinc-900');
    expect(buttonByTitle(wrapper, 'rich-content.align_left')!.classes().join(' ')).not.toContain('bg-zinc-900');
  });

  it('writes the alignment the author picks', async () => {
    const { wrapper, chain, run } = mountMenu();

    await buttonByTitle(wrapper, 'rich-content.align_left')!.trigger('click');

    expect(chain.updateAttributes).toHaveBeenCalledWith('image', { align: 'left' });
    expect(run).toHaveBeenCalled();
  });

  it('names the stored width so the size trigger is not a mystery', () => {
    expect(mountMenu({ width: '500px' }).wrapper.text()).toContain('rich-content.size_medium');
    expect(mountMenu({ width: '100%' }).wrapper.text()).toContain('rich-content.size_full');
    expect(mountMenu({ width: '473px' }).wrapper.text()).toContain('473px');
    expect(mountMenu().wrapper.text()).toContain('rich-content.size_auto');
  });

  it('flags a missing alt text, and stays quiet once there is one', () => {
    expect(mountMenu().wrapper.text()).toContain('rich-content.image_alt_missing');
    expect(mountMenu({ alt: 'Studentai' }).wrapper.text()).not.toContain('rich-content.image_alt_missing');
  });

  it('removes the image through the editor', async () => {
    const { wrapper, run } = mountMenu();

    await buttonByTitle(wrapper, 'rich-content.image_remove')!.trigger('click');

    expect(run).toHaveBeenCalled();
  });
});
