import type { Editor } from '@tiptap/core';

interface BubbleMenuContext {
  editor: Editor;
  from: number;
  to: number;
}

/**
 * The bubble menus are exact complements: whatever the selection, at most one is up.
 * Precedence is image → text selection → link under the cursor → table under the cursor.
 * Keeping every predicate here is what stops them overlapping. The cursor-driven menus
 * also need focus: a collapsed cursor outlives blur, and the menu would linger over the form.
 */
export function shouldShowImageBubbleMenu({ editor }: { editor: Editor }): boolean {
  return editor.isActive('image');
}

export function shouldShowTextBubbleMenu({ editor, from, to }: BubbleMenuContext): boolean {
  return !editor.isActive('image') && from !== to;
}

export function shouldShowLinkBubbleMenu({ editor, from, to }: BubbleMenuContext): boolean {
  return editor.isFocused && !editor.isActive('image') && from === to && editor.isActive('link');
}

export function shouldShowTableBubbleMenu({ editor, from, to }: BubbleMenuContext): boolean {
  return editor.isFocused && !editor.isActive('image') && from === to && !editor.isActive('link') && editor.isActive('table');
}
