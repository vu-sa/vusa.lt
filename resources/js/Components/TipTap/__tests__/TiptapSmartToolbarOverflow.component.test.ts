import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import type { Editor } from '@tiptap/core';

import TiptapSmartToolbarOverflow from '../TiptapSmartToolbarOverflow.vue';

import { commonStubs } from '@/tests/stubs';

function makeEditor(inTable = false): Editor {
  return {
    isActive: vi.fn((name: string) => inTable && name === 'table'),
    getAttributes: vi.fn(() => ({})),
    can: vi.fn(() => ({
      mergeCells: () => true,
      splitCell: () => true,
    })),
    chain: vi.fn(),
    commands: {},
  } as unknown as Editor;
}

const stubs = {
  ...commonStubs,
  DropdownMenuSub: { template: '<div><slot /></div>' },
  DropdownMenuSubContent: { template: '<div><slot /></div>' },
  DropdownMenuSubTrigger: { template: '<div><slot /></div>' },
  TiptapVideoButton: { template: '<div><slot /></div>' },
  TiptapYoutubeButton: { template: '<div><slot /></div>' },
};

describe('TiptapSmartToolbarOverflow', () => {
  it('exposes the full editor actions that do not fit in the one-row toolbar', () => {
    const wrapper = mount(TiptapSmartToolbarOverflow, {
      props: { editor: makeEditor() },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('rich-content.clear_formatting');
    expect(wrapper.text()).toContain('rich-content.heading_style');
    expect(wrapper.text()).toContain('rich-content.alignment');
    expect(wrapper.text()).toContain('rich-content.tag');
    expect(wrapper.text()).toContain('rich-content.insert_horizontal_rule');
    expect(wrapper.text()).toContain('rich-content.insert_youtube');
    expect(wrapper.text()).toContain('rich-content.insert_video');
  });

  it('exposes the full table toolset while editing a table', () => {
    const wrapper = mount(TiptapSmartToolbarOverflow, {
      props: { editor: makeEditor(true) },
      global: { stubs },
    });

    expect(wrapper.text()).toContain('rich-content.table_toggle_header');
    expect(wrapper.text()).toContain('rich-content.table_add_column');
    expect(wrapper.text()).toContain('rich-content.table_add_row');
    expect(wrapper.text()).toContain('rich-content.table_merge_cells');
    expect(wrapper.text()).toContain('rich-content.table_split_cell');
    expect(wrapper.text()).toContain('rich-content.table_delete_column');
    expect(wrapper.text()).toContain('rich-content.table_delete_row');
    expect(wrapper.text()).toContain('rich-content.table_fix');
  });
});
