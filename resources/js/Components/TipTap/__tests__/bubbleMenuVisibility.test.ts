import { describe, it, expect } from 'vitest';
import type { Editor } from '@tiptap/core';

import { shouldShowImageBubbleMenu, shouldShowTextBubbleMenu } from '../bubbleMenuVisibility';

function editorWith(activeNode: string | null): Editor {
  return { isActive: (name: string) => name === activeNode } as unknown as Editor;
}

describe('bubble menu visibility', () => {
  it('shows the text menu for a real text selection', () => {
    expect(shouldShowTextBubbleMenu({ editor: editorWith(null), from: 3, to: 9 })).toBe(true);
  });

  it('leaves the text menu hidden for a collapsed cursor', () => {
    expect(shouldShowTextBubbleMenu({ editor: editorWith(null), from: 5, to: 5 })).toBe(false);
  });

  // Regression: selecting an image used to raise the text menu (bold/italic/link),
  // none of which do anything to an image.
  it('hands a selected image over to the image menu', () => {
    const editor = editorWith('image');

    expect(shouldShowTextBubbleMenu({ editor, from: 4, to: 5 })).toBe(false);
    expect(shouldShowImageBubbleMenu({ editor })).toBe(true);
  });

  it('keeps the image menu down when no image is selected', () => {
    expect(shouldShowImageBubbleMenu({ editor: editorWith('paragraph') })).toBe(false);
  });

  it('never raises both menus at once', () => {
    for (const active of [null, 'paragraph', 'heading', 'image']) {
      const editor = editorWith(active);
      const both = shouldShowTextBubbleMenu({ editor, from: 1, to: 6 })
        && shouldShowImageBubbleMenu({ editor });

      expect(both).toBe(false);
    }
  });
});
