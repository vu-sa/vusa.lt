import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

// `debouncedFetchPreview` echoes back one fake resolved payload per requested part,
// keyed by whatever `key` RCFullscreenEditor sent — lets the assertions below check
// exactly what the editor asked for (which parts, under which key) without a real
// network round trip.
const fetchPreviewMock = vi.fn(async (parts: { key: string; type: string }[]) =>
  Object.fromEntries(parts.map(part => [part.key, { type: part.type, items: ['fake-item'] }])));

vi.mock('../../../composables/useContentPartPreview', () => ({
  useContentPartPreview: () => ({ debouncedFetchPreview: fetchPreviewMock }),
}));

import RCFullscreenEditor from '../RCFullscreenEditor.vue';
import { commonStubs } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  BlockPickerDialog: { template: '<div />' },
  DarkModeButton: { template: '<button class="dark-mode-toggle" />' },
  RCFullscreenBlock: {
    props: ['content', 'resolved', 'blockKey'],
    template: '<div class="rc-block-stub" :data-block-key="blockKey" :data-resolved="JSON.stringify(resolved)" />',
  },
  RCInsertAffordance: { template: '<div />' },
  RCSideBySideDialog: { template: '<div />' },
};

describe('RCFullscreenEditor', () => {
  beforeEach(() => {
    fetchPreviewMock.mockClear();
  });

  it('emits save from the toolbar so the parent form can persist the document', async () => {
    const wrapper = mount(RCFullscreenEditor, {
      props: {
        contents: [],
        history: { commit: () => {}, undo: () => {}, redo: () => {}, canUndo: false, canRedo: false },
      },
      global: { stubs },
    });

    await wrapper.findAll('button').find(button => button.text().includes('Išsaugoti'))!.trigger('click');

    expect(wrapper.emitted('save')).toHaveLength(1);
  });

  it('shows a clear editing instruction until the author switches to preview', () => {
    const wrapper = mount(RCFullscreenEditor, {
      props: {
        contents: [],
        history: { commit: () => {}, undo: () => {}, redo: () => {}, canUndo: false, canRedo: false },
      },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('rich-content.edit_mode');
    expect(wrapper.text()).toContain('rich-content.edit_mode_hint');
  });

  it('fetches and shows preview data for a not-yet-saved server-resolved block (no id)', async () => {
    const wrapper = mount(RCFullscreenEditor, {
      props: {
        contents: [{ type: 'event-list', json_content: {}, options: {}, key: 'unsaved-1' }],
        history: { commit: () => {}, undo: () => {}, redo: () => {}, canUndo: false, canRedo: false },
      },
      global: { stubs },
    });

    await flushPromises();

    // A block that hasn't been saved (no `id`) must still be requested and rendered
    // with its real fetched data, keyed by its client-side `key` — not skipped, which
    // was the "preview doesn't show the fetched events" bug this covers.
    expect(fetchPreviewMock).toHaveBeenCalledWith([
      { key: 'unsaved-1', type: 'event-list', json_content: {}, options: {} },
    ]);
    const block = wrapper.get('.rc-block-stub');
    expect(block.attributes('data-block-key')).toBe('unsaved-1');
    expect(JSON.parse(block.attributes('data-resolved')!)).toEqual({ type: 'event-list', items: ['fake-item'] });
  });

  it('ignores a superseded preview fetch (resolves undefined) instead of crashing or clearing the shown data', async () => {
    // Reproduces the actual reported bug: rapidly changing a toolbar option (e.g. the
    // limit NumberField) re-fires the watcher before the previous debounced call
    // settles, and vueuse's `useDebounceFn` resolves that superseded call's promise to
    // `undefined` rather than the newer result.
    const wrapper = mount(RCFullscreenEditor, {
      props: {
        contents: [{ type: 'event-list', json_content: {}, options: { limit: 3 }, key: 'block-1' }],
        history: { commit: () => {}, undo: () => {}, redo: () => {}, canUndo: false, canRedo: false },
      },
      global: { stubs },
    });
    await flushPromises();
    const block = wrapper.get('.rc-block-stub');
    expect(JSON.parse(block.attributes('data-resolved')!)).toEqual({ type: 'event-list', items: ['fake-item'] });

    fetchPreviewMock.mockResolvedValueOnce(undefined);
    await wrapper.setProps({ contents: [{ type: 'event-list', json_content: {}, options: { limit: 5 }, key: 'block-1' }] });
    await flushPromises();

    // Still shows the previously fetched data — neither crashed nor cleared.
    expect(JSON.parse(block.attributes('data-resolved')!)).toEqual({ type: 'event-list', items: ['fake-item'] });
  });

  it('never requests preview data for a non-server-resolved type (e.g. tiptap)', async () => {
    mount(RCFullscreenEditor, {
      props: {
        contents: [{ type: 'tiptap', json_content: {}, options: {}, key: 'text-1' }],
        history: { commit: () => {}, undo: () => {}, redo: () => {}, canUndo: false, canRedo: false },
      },
      global: { stubs },
    });

    await flushPromises();

    expect(fetchPreviewMock).not.toHaveBeenCalled();
  });
});
