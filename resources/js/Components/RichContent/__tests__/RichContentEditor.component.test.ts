import { describe, expect, it } from 'vitest';
import { mount, type DOMWrapper } from '@vue/test-utils';

import RichContentEditor from '../RichContentEditor.vue';

import { commonStubs } from '@/tests/stubs';

/**
 * ContentEditorFactory pulls in the full editor/display component graph via Suspense —
 * irrelevant to what this test covers (the editor's own chrome: collapse state, drag
 * handle placement, block header controls), so it's stubbed out.
 */
const stubs = {
  ...commonStubs,
  'ContentEditorFactory': {
    props: ['presentationDisabled'],
    template: '<div class="content-editor-factory-stub" :data-presentation-disabled="presentationDisabled" />',
  },
  'TextBoxSubmissionsDialog': true,
  'RCFullscreenEditor': {
    props: ['contents', 'tenantId', 'history'],
    emits: ['update:contents', 'close'],
    template: '<div class="fullscreen-editor-stub" />',
  },
  // Render the real TransitionGroup rather than vue-test-utils' default stub, since
  // the block list relies on it for drag reordering.
  'transition-group': false,
};

function makeParts(count: number) {
  return Array.from({ length: count }, (_, i) => ({
    id: i + 1,
    type: 'tiptap',
    json_content: { type: 'doc', content: [{ type: 'paragraph', content: [{ type: 'text', text: `Block ${i + 1}` }] }] },
    options: {},
  }));
}

/**
 * `.isVisible()` goes through jsdom's `getComputedStyle`, which has been observed here
 * to return a stale cached `display` value across a v-show toggle within the same test
 * (the live DOM — `element.style.display` / outerHTML — is correct at that point, only
 * the computed-style snapshot lags). Check the CSSOM property v-show actually sets
 * instead, which isn't affected by that caching.
 */
function isHidden(wrapper: DOMWrapper<Element>): boolean {
  return (wrapper.element as HTMLElement).style.display === 'none';
}

async function mountEditor(contents: ReturnType<typeof makeParts>) {
  const wrapper = mount(RichContentEditor, {
    props: { contents, 'onUpdate:contents': (val: unknown) => wrapper.setProps({ contents: val }) },
    global: { stubs },
  });
  await new Promise(resolve => setTimeout(resolve, 320)); // clears the initial-loading skeleton
  await wrapper.vm.$nextTick();
  return wrapper;
}

describe('RichContentEditor', () => {
  it('renders one block card per content item', async () => {
    const wrapper = await mountEditor(makeParts(2));
    expect(wrapper.findAll('[data-rc-block-body]')).toHaveLength(2);
  });

  it('puts the drag handle inside the block header, not floating outside it', async () => {
    const wrapper = await mountEditor(makeParts(2));
    const handles = wrapper.findAll('.rc-drag-handle');
    expect(handles).toHaveLength(2);
    // The handle must be a descendant of the bordered block card, not a sibling
    // absolutely positioned outside it (the old `-left-6` floating handle).
    handles.forEach((handle) => {
      expect(handle.element.closest('.border')).not.toBeNull();
    });
  });

  it('auto-collapses everything but the first block when there are more than 4', async () => {
    const wrapper = await mountEditor(makeParts(5));
    const bodies = wrapper.findAll('[data-rc-block-body]');
    expect(isHidden(bodies[0]!)).toBe(false);
    for (let i = 1; i < bodies.length; i++) {
      expect(isHidden(bodies[i]!)).toBe(true);
    }
  });

  it('does not auto-collapse when there are 4 or fewer blocks', async () => {
    const wrapper = await mountEditor(makeParts(4));
    wrapper.findAll('[data-rc-block-body]').forEach(body => expect(isHidden(body)).toBe(false));
  });

  it('keeps independent bands after a section presentation-editable in forms mode', async () => {
    const wrapper = await mountEditor([
      { id: 1, type: 'section', json_content: {}, options: { wraps: 'following' } },
      { id: 2, type: 'content-grid', json_content: {}, options: {} },
      { id: 3, type: 'card-stack', json_content: {}, options: {} },
    ]);
    const factories = wrapper.findAll('.content-editor-factory-stub');

    expect(factories[0]!.attributes('data-presentation-disabled')).toBeUndefined();
    expect(factories[1]!.attributes('data-presentation-disabled')).toBe('true');
    expect(factories[2]!.attributes('data-presentation-disabled')).toBeUndefined();
  });

  it('collapse all / expand all toggle every block', async () => {
    const wrapper = await mountEditor(makeParts(3));

    await wrapper.findAll('button').find(b => b.text() === 'rich-content.collapse_all')!.trigger('click');
    await wrapper.vm.$nextTick();
    wrapper.findAll('[data-rc-block-body]').forEach(body => expect(isHidden(body)).toBe(true));

    await wrapper.findAll('button').find(b => b.text() === 'rich-content.expand_all')!.trigger('click');
    await wrapper.vm.$nextTick();
    wrapper.findAll('[data-rc-block-body]').forEach(body => expect(isHidden(body)).toBe(false));
  });

  it('clicking a block header toggles just that block', async () => {
    const wrapper = await mountEditor(makeParts(2));
    const firstBlockToggle = wrapper.findAll('button').find(b => b.attributes('title')?.includes('block'))!;

    await firstBlockToggle.trigger('click');
    const bodies = wrapper.findAll('[data-rc-block-body]');
    expect(isHidden(bodies[0]!)).toBe(true);
    expect(isHidden(bodies[1]!)).toBe(false);
  });

  it('the fullscreen button opens RCFullscreenEditor and hides the forms-mode block list', async () => {
    const wrapper = await mountEditor(makeParts(2));
    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(false);
    expect(wrapper.findAll('[data-rc-block-body]')).toHaveLength(2);

    await wrapper.findAll('button').find(b => b.text().includes('rich-content.fullscreen_editor'))!.trigger('click');

    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(true);
    expect(wrapper.findAll('[data-rc-block-body]')).toHaveLength(0);
  });

  it('closing the full-screen editor brings the forms-mode block list back', async () => {
    const wrapper = await mountEditor(makeParts(2));
    await wrapper.findAll('button').find(b => b.text().includes('rich-content.fullscreen_editor'))!.trigger('click');
    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(true);

    await wrapper.getComponent(stubs['RCFullscreenEditor']).vm.$emit('close');
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.fullscreen-editor-stub').exists()).toBe(false);
    expect(wrapper.findAll('[data-rc-block-body]')).toHaveLength(2);
  });
});
