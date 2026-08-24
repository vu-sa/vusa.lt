import type { Editor } from '@tiptap/core';

interface BubbleMenuContext {
  editor: Editor;
  from: number;
  to: number;
}

/**
 * The two bubble menus are exact complements: whatever the selection, at most one of
 * them is up. Keeping both predicates here is what stops them overlapping — a selected
 * image used to get the text menu offering bold/italic/link, none of which apply.
 */
export function shouldShowTextBubbleMenu({ editor, from, to }: BubbleMenuContext): boolean {
  return !editor.isActive('image') && from !== to;
}

export function shouldShowImageBubbleMenu({ editor }: { editor: Editor }): boolean {
  return editor.isActive('image');
}
