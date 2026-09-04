import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import RCFullscreenEditor from '../RCFullscreenEditor.vue';
import { commonStubs } from '@/tests/stubs';

const stubs = {
  ...commonStubs,
  BlockPickerDialog: { template: '<div />' },
  DarkModeButton: { template: '<button class="dark-mode-toggle" />' },
  RCFullscreenBlock: { template: '<div />' },
  RCInsertAffordance: { template: '<div />' },
  RCSideBySideDialog: { template: '<div />' },
};

describe('RCFullscreenEditor', () => {
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
});
