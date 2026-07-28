import Heading from '@tiptap/extension-heading';
import { mergeAttributes } from '@tiptap/core';

import { latinizeId } from '@/Utils/String';

export type HeadingSize = 'sm' | 'md' | 'lg' | 'xl';
export type HeadingAccent = 'none' | 'red' | 'yellow' | 'zinc';
// `default` carries no class — it falls through to the level-based margin-block-start
// rules in app.css. Only non-default densities render a class.
export type HeadingSpacing = 'default' | 'tight' | 'loose' | 'none';

const SIZE_CLASS: Record<HeadingSize, string> = {
  sm: 'rc-h-sm',
  md: 'rc-h-md',
  lg: 'rc-h-lg',
  xl: 'rc-h-xl',
};

const ACCENT_CLASS: Record<Exclude<HeadingAccent, 'none'>, string> = {
  red: 'rc-h-accent-red',
  yellow: 'rc-h-accent-yellow',
  zinc: 'rc-h-accent-zinc',
};

const SPACING_CLASS: Record<Exclude<HeadingSpacing, 'default'>, string> = {
  tight: 'rc-h-spacing-tight',
  loose: 'rc-h-spacing-loose',
  none: 'rc-h-spacing-none',
};

function sizeFromClassList(classList: DOMTokenList): HeadingSize | null {
  return (Object.keys(SIZE_CLASS) as HeadingSize[]).find(size => classList.contains(SIZE_CLASS[size])) ?? null;
}

function accentFromClassList(classList: DOMTokenList): HeadingAccent | null {
  return (Object.keys(ACCENT_CLASS) as Exclude<HeadingAccent, 'none'>[])
    .find(accent => classList.contains(ACCENT_CLASS[accent])) ?? null;
}

function spacingFromClassList(classList: DOMTokenList): HeadingSpacing | null {
  return (Object.keys(SPACING_CLASS) as Exclude<HeadingSpacing, 'default'>[])
    .find(spacing => classList.contains(SPACING_CLASS[spacing])) ?? null;
}

/**
 * Extends TipTap's Heading with two authorable attributes the mascot-style heading
 * needed and the plain h2/h3 toggle couldn't express: `size` (independent of the
 * semantic h2/h3/h4 level — a smaller heading inside a two-column layout, for
 * instance) and `accent` (a brand-color treatment). `id` is unchanged — still
 * computed from the heading's own text via `latinizeId`, for ToC/anchor links). Both
 * new attributes render as a `class`, never inline `style`: `HtmlSanitizerService`
 * strips `style` on every element but already allowlists `class` on h2/h3/h4, so a
 * class is the only round-trippable option here.
 */
export const CustomHeading = Heading.extend({
  addAttributes() {
    return {
      ...this.parent?.(),
      id: {
        default: null,
      },
      // No per-attribute `renderHTML` — both are folded into the single `class`
      // string the node's own `renderHTML` below builds, rather than serialized as
      // their own (non-standard, sanitizer-stripped) HTML attributes.
      size: {
        default: null,
        renderHTML: () => ({}),
      },
      accent: {
        default: null,
        renderHTML: () => ({}),
      },
      spacing: {
        default: null,
        renderHTML: () => ({}),
      },
    };
  },
  parseHTML() {
    // `level` must come out of `getAttrs`, not a sibling static `attrs: { level }` —
    // this rule already needs `getAttrs` for id/size/accent, and when both are present
    // on a ProseMirror parse rule, `getAttrs`'s return value is what actually populates
    // the node's attributes; a rule with only a static `attrs.level` alongside a
    // `getAttrs` that doesn't also return `level` left every parsed heading's level at
    // its schema default (1) — invalid for `levels: [2,3,(4)]`, so `renderHTML`'s
    // `hasLevel` fallback silently downgraded every reparsed h3 (and now h4) to h2.
    // This is what pasting HTML into the editor exercises; the JSON `content`/`options`
    // round-trip (save → reload) never goes through `parseHTML` at all.
    return [2, 3, 4].map(level => ({
      tag: `h${level}`,
      getAttrs: (dom: HTMLElement) => ({
        level,
        id: dom.getAttribute('id'),
        size: sizeFromClassList(dom.classList),
        accent: accentFromClassList(dom.classList),
        spacing: spacingFromClassList(dom.classList),
      }),
    }));
  },
  renderHTML({ node, HTMLAttributes }) {
    const hasLevel = this.options.levels.includes(node.attrs.level);
    const level = hasLevel ? node.attrs.level : this.options.levels[0];

    // `node.textContent` — a real ProseMirror `Node` getter — not a hand-rolled
    // recursive walk over `node.content`: that's a `Fragment` instance here (both in
    // the live editor and in `generateHTML()`'s reconstructed doc, e.g.
    // RichContentTiptapHTML.vue's preview), not a plain array, so a manual
    // `Array.isArray(node.content)` check silently produced an empty id on every
    // heading rendered through either path. `TiptapEditor.vue`'s own
    // `updateHeadingIds()` already uses `node.textContent` correctly — this now matches.
    const id = latinizeId(node.textContent);

    const size = node.attrs.size as HeadingSize | null;
    const accent = node.attrs.accent as HeadingAccent | null;
    const spacing = node.attrs.spacing as HeadingSpacing | null;
    const classes = [
      size ? SIZE_CLASS[size] : null,
      accent && accent !== 'none' ? ACCENT_CLASS[accent] : null,
      spacing && spacing !== 'default' ? SPACING_CLASS[spacing] : null,
    ].filter(Boolean).join(' ');

    return [
      `h${level}`,
      mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, {
        id,
        class: classes || null,
      }),
      0,
    ];
  },
});
