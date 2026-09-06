import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RichContentEditor from '../RichContentEditor.vue';
import { commonStubs } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  'RCFullscreenEditor': {
    props: ['contents', 'tenantId', 'history'],
    emits: ['update:contents', 'close'],
    template: '<div class="fullscreen-editor-stub" />',
  },
  'SpotlightPopover': {
    props: ['title', 'description', 'isDismissed', 'position'],
    emits: ['dismiss'],
    template: '<div class="spotlight-popover-stub" :data-dismissed="isDismissed"><slot /></div>',
  },
};

function makeParts(count: number) {
  return Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    type: 'tiptap',
    json_content: { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: `Block ${i + 1}` }] }] },
    options: {},
  }));
}

function mountEditor(contents: ReturnType<typeof makeParts>) {
  const wrapper = mount(RichContentEditor, {
    props: { contents, 'onUpdate:contents': (val: unknown) => wrapper.setProps({ contents: val }) },
    global: { stubs },
  });
  return wrapper;
}

describe('RichContentEditor', () => {
  it('renders a launcher card with the block count and block summary chips', () => {
    const wrapper = mountEditor(makeParts(2));
    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(false);
    expect(wrapper.text()).toContain('rich-content.blocks_count');
    // Two block badges rendered
    expect(wrapper.findAll('.max-w-\\[14rem\\]')).toHaveLength(2);
  });

  it('renders an empty state when contents array is empty', () => {
    const wrapper = mountEditor([]);
    expect(wrapper.text()).toContain('rich-content.content_empty');
    expect(wrapper.text()).toContain('rich-content.content_empty_description');
    expect(wrapper.findAll('.max-w-\\[14rem\\]')).toHaveLength(0);
  });

  it('renders SpotlightPopover wrapping the edit content button', () => {
    const wrapper = mountEditor(makeParts(1));
    const spotlight = wrapper.find('.spotlight-popover-stub');
    expect(spotlight.exists()).toBe(true);
    expect(spotlight.find('button').text()).toContain('rich-content.edit_content');
  });

  it('clicking the edit button opens RCFullscreenEditor', async () => {
    const wrapper = mountEditor(makeParts(2));
    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(false);

    const button = wrapper.find('button');
    await button.trigger('click');

    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(true);
  });

  it('closing the full-screen editor brings the launcher card back', async () => {
    const wrapper = mountEditor(makeParts(2));
    await wrapper.find('button').trigger('click');
    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(true);

    await wrapper.getComponent(stubs['RCFullscreenEditor']).vm.$emit('close');
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(false);
  });
});
