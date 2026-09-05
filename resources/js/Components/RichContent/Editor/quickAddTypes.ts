import { getContentType, type ContentType } from '../Types';

/**
 * The curated "most common" subset offered as one-click buttons — both in forms mode
 * (RichContentEditor.vue's toolbar + insert-between menu) and the full-screen editor
 * (RCFullscreenEditor's insert-between menu + trailing "add block" affordance). Extracted
 * so the list can't drift between the two editing modes; the full type list remains
 * available via BlockPickerDialog.
 */
export function getQuickAddTypes(): ContentType[] {
  return [
    getContentType('tiptap'),
    getContentType('shadcn-card'),
    getContentType('content-grid'),
    getContentType('image-grid'),
    getContentType('hero'),
    getContentType('shadcn-accordion'),
    getContentType('social-embed'),
    getContentType('spotify-embed'),
    getContentType('section'),
    getContentType('person-quote'),
    getContentType('spacer'),
  ];
}
