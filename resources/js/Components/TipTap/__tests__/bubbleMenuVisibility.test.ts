import { describe, it, expect } from 'vitest';
import type { Editor } from '@tiptap/core';

import {
  shouldShowImageBubbleMenu,
  shouldShowLinkBubbleMenu,
  shouldShowTableBubbleMenu,
  shouldShowTextBubbleMenu,
} from '../bubbleMenuVisibility';

function editorWith(active: string[] = [], isFocused = true): Editor {
  return { isFocused, isActive: (name: string) => active.includes(name) } as unknown as Editor;
}

const menus = (editor: Editor, from: number, to: number) => [
  shouldShowImageBubbleMenu({ editor }),
  shouldShowTextBubbleMenu({ editor, from, to }),
  shouldShowLinkBubbleMenu({ editor, from, to }),
  shouldShowTableBubbleMenu({ editor, from, to }),
];

describe('bubble menu visibility', () => {
  it('shows the text menu for a real text selection', () => {
    expect(shouldShowTextBubbleMenu({ editor: editorWith(), from: 3, to: 9 })).toBe(true);
  });

  it('leaves the text menu hidden for a collapsed cursor', () => {
    expect(shouldShowTextBubbleMenu({ editor: editorWith(), from: 5, to: 5 })).toBe(false);
  });

  // Regression: selecting an image used to raise the text menu (bold/italic/link),
  // none of which do anything to an image.
  it('hands a selected image over to the image menu', () => {
    const editor = editorWith(['image']);

    expect(shouldShowTextBubbleMenu({ editor, from: 4, to: 5 })).toBe(false);
    expect(shouldShowImageBubbleMenu({ editor })).toBe(true);
  });

  it('offers link actions only while the cursor rests in a link', () => {
    expect(shouldShowLinkBubbleMenu({ editor: editorWith(['link']), from: 4, to: 4 })).toBe(true);
    expect(shouldShowLinkBubbleMenu({ editor: editorWith(['link']), from: 2, to: 6 })).toBe(false);
    expect(shouldShowLinkBubbleMenu({ editor: editorWith([]), from: 4, to: 4 })).toBe(false);
  });

  it('offers table actions only while the cursor rests in a table', () => {
    expect(shouldShowTableBubbleMenu({ editor: editorWith(['table']), from: 4, to: 4 })).toBe(true);
    expect(shouldShowTableBubbleMenu({ editor: editorWith([]), from: 4, to: 4 })).toBe(false);
  });

  // A collapsed cursor survives blur; without this the menu lingered over the rest of the form.
  it('drops cursor menus once the editor loses focus', () => {
    expect(shouldShowLinkBubbleMenu({ editor: editorWith(['link'], false), from: 4, to: 4 })).toBe(false);
    expect(shouldShowTableBubbleMenu({ editor: editorWith(['table'], false), from: 4, to: 4 })).toBe(false);
  });

  it('never raises two menus at once', () => {
    const states = [[], ['paragraph'], ['image'], ['link'], ['table'], ['link', 'table'], ['image', 'table']];

    for (const active of states) {
      for (const [from, to] of [[4, 4], [1, 6]]) {
        const shown = menus(editorWith(active), from, to).filter(Boolean);
        expect(shown.length, `${active.join('+') || 'none'} ${from}-${to}`).toBeLessThanOrEqual(1);
      }
    }
  });
});
