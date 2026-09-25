import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import type { Editor } from '@tiptap/core';

import TiptapMoreMenu from '../TiptapMoreMenu.vue';
import { toolsFor } from '../toolbarProfiles';

import { commonStubs } from '@/tests/stubs';

function makeEditor(active: string[] = []): Editor {
  return {
    isActive: vi.fn((name: string) => active.includes(name)),
    getAttributes: vi.fn(() => ({})),
    can: vi.fn(() => ({ undo: () => true, redo: () => true })),
    chain: vi.fn(),
    commands: {},
  } as unknown as Editor;
}

const stubs = {
  ...commonStubs,
  DropdownMenuSub: { template: '<div><slot /></div>' },
  DropdownMenuSubContent: { template: '<div><slot /></div>' },
  DropdownMenuSubTrigger: { template: '<div><slot /></div>' },
  TiptapYoutubeButton: true,
  ImageSelector: true,
};

function mountMenu(props: Record<string, unknown>) {
  return mount(TiptapMoreMenu, { props, global: { stubs } });
}

describe('TiptapMoreMenu', () => {
  it('exposes the full editor actions that do not fit in the canvas toolbar', () => {
    const wrapper = mountMenu({ editor: makeEditor(), tools: toolsFor('full'), includeInserts: true });

    expect(wrapper.text()).toContain('rich-content.clear_formatting');
    expect(wrapper.text()).toContain('rich-content.alignment');
    expect(wrapper.text()).toContain('rich-content.tag');
    expect(wrapper.text()).toContain('rich-content.insert_horizontal_rule');
    expect(wrapper.text()).toContain('rich-content.insert_youtube');
    expect(wrapper.text()).toContain('rich-content.insert_video');
  });

  it('offers heading style only while a heading is being edited', () => {
    expect(mountMenu({ editor: makeEditor(), tools: toolsFor('full') }).text()).not.toContain('rich-content.heading_style');
    expect(mountMenu({ editor: makeEditor(['heading']), tools: toolsFor('full') }).text()).toContain('rich-content.heading_style');
  });

  it('keeps a record description to quote and clear formatting', () => {
    const wrapper = mountMenu({ editor: makeEditor(['heading']), tools: toolsFor('description') });

    expect(wrapper.text()).toContain('rich-content.blockquote');
    expect(wrapper.text()).toContain('rich-content.clear_formatting');
    expect(wrapper.text()).not.toContain('rich-content.tag');
    expect(wrapper.text()).not.toContain('rich-content.alignment');
    expect(wrapper.text()).not.toContain('rich-content.heading_style');
  });

  it('offers removing a tag only when the cursor is in one', () => {
    expect(mountMenu({ editor: makeEditor(), tools: toolsFor('full') }).text()).not.toContain('rich-content.tag_remove');
    expect(mountMenu({ editor: makeEditor(['rcTag']), tools: toolsFor('full') }).text()).toContain('rich-content.tag_remove');
  });

  it('carries undo and redo for phones when asked', () => {
    const wrapper = mountMenu({ editor: makeEditor(), tools: toolsFor('description'), showHistory: true });

    expect(wrapper.text()).toContain('rich-content.undo');
    expect(wrapper.text()).toContain('rich-content.redo');
  });
});
