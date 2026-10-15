import type { Editor } from '@tiptap/core';
import { computed, ref, toValue, type MaybeRefOrGetter } from 'vue';

import type { HeadingAccent, HeadingSize, HeadingSpacing } from '../CustomHeading';
import type { RCTagColor, RCTagVariant } from '../RCTag';
import type { TextAlignValue } from '../TextAlign';

export type HeadingLevelValue = 'paragraph' | '2' | '3' | '4';

/** Archive documents get their own link class so the public site can badge them. */
export const DOCUMENT_LINK_CLASS = 'archive-document-link plain';

/**
 * Every toolbar (form field, canvas smart toolbar, overflow menu) goes through these,
 * so a fix to how a link or heading is applied lands everywhere at once.
 */
export function useTiptapCommands(source: MaybeRefOrGetter<Editor | null | undefined>) {
  const editor = computed(() => toValue(source) ?? null);
  const tagVariant = ref<RCTagVariant>('filled');

  const currentHeadingLevel = computed<HeadingLevelValue>(() => {
    for (const level of ['2', '3', '4'] as const) {
      if (editor.value?.isActive('heading', { level: Number(level) })) {
        return level;
      }
    }
    return 'paragraph';
  });

  /** `setHeading`, not `toggleHeading`: picking the current level again must not demote it. */
  function setHeadingLevel(value: HeadingLevelValue | string): void {
    if (!editor.value) return;
    if (value === 'paragraph') {
      editor.value.chain().focus().setParagraph().run();
      return;
    }
    editor.value.chain().focus().setHeading({ level: Number(value) as 2 | 3 | 4 }).run();
  }

  const currentHeadingSize = computed<HeadingSize | null>(
    () => (editor.value?.getAttributes('heading').size as HeadingSize | undefined) ?? null,
  );
  const currentHeadingAccent = computed<HeadingAccent>(
    () => (editor.value?.getAttributes('heading').accent as HeadingAccent | undefined) ?? 'none',
  );
  const currentHeadingSpacing = computed<HeadingSpacing>(
    () => (editor.value?.getAttributes('heading').spacing as HeadingSpacing | undefined) ?? 'default',
  );

  function setHeadingAttribute(attribute: 'size' | 'accent' | 'spacing', value: string): void {
    editor.value?.chain().focus().updateAttributes('heading', { [attribute]: value }).run();
  }

  const alignTarget = () => (editor.value?.isActive('heading') ? 'heading' : 'paragraph');

  const currentAlignment = computed<TextAlignValue>(
    () => (editor.value?.getAttributes(alignTarget()).align as TextAlignValue | undefined) ?? 'start',
  );

  function setAlignment(align: TextAlignValue): void {
    editor.value?.chain().focus().updateAttributes(alignTarget(), { align }).run();
  }

  function applyTag(color: RCTagColor): void {
    editor.value?.chain().focus().setRCTag({ variant: tagVariant.value, color }).run();
  }

  function removeTag(): void {
    editor.value?.chain().focus().unsetRCTag().run();
  }

  /**
   * Edits the link under a selection, or inserts `text` as a new link. Inserted as a
   * node, never an HTML string, so quotes or `<` in a URL or label cannot break markup.
   */
  function insertLink(url: string, text?: string, options: { document?: boolean } = {}): void {
    if (!editor.value || !url) return;

    const attrs = { href: url, class: options.document ? DOCUMENT_LINK_CLASS : null };
    const { from, to } = editor.value.state.selection;

    if (from !== to) {
      editor.value.chain().focus().extendMarkRange('link').setLink(attrs).run();
      return;
    }

    editor.value.chain().focus().insertContent({
      type: 'text',
      text: text?.trim() || url,
      marks: [{ type: 'link', attrs }],
    }).run();
  }

  function unsetLink(): void {
    editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
  }

  function insertImage(image: { src: string; alt?: string; title?: string } | string): void {
    if (!editor.value) return;
    const attrs = typeof image === 'string' ? { src: image } : { src: image.src, alt: image.alt ?? '', title: image.title ?? '' };
    editor.value.chain().focus().setImage(attrs).run();
  }

  function insertVideo(url: string): void {
    editor.value?.chain().focus().setVideo(url).run();
  }

  function insertYoutube(url: string): void {
    if (!url.trim()) return;
    editor.value?.chain().focus().setYoutubeVideo({ src: url.trim() }).run();
  }

  function insertTable(): void {
    editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
  }

  function insertHorizontalRule(): void {
    editor.value?.chain().focus().setHorizontalRule().run();
  }

  function clearFormatting(): void {
    editor.value?.chain().focus().unsetAllMarks().run();
  }

  return {
    editor,
    tagVariant,
    currentHeadingLevel,
    setHeadingLevel,
    currentHeadingSize,
    currentHeadingAccent,
    currentHeadingSpacing,
    setHeadingAttribute,
    currentAlignment,
    setAlignment,
    applyTag,
    removeTag,
    insertLink,
    unsetLink,
    insertImage,
    insertVideo,
    insertYoutube,
    insertTable,
    insertHorizontalRule,
    clearFormatting,
  };
}
