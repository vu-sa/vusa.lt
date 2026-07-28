import { Extension } from '@tiptap/core';

/**
 * Adds an authorable `align` attribute to headings and paragraphs — hand-built rather
 * than the official `@tiptap/extension-text-align` package (not an existing
 * dependency; adding one needs approval per AGENTS.md) and because that package's
 * default `renderHTML` writes an inline `style="text-align: …"`, which
 * `HtmlSanitizerService` strips from every element on write — the alignment would
 * silently vanish the moment content is saved. Rendering a class instead
 * (`rc-align-start` / `rc-align-center` / `rc-align-end`) survives sanitization the
 * same way `CustomHeading`'s size/accent classes do.
 */
export type TextAlignValue = 'start' | 'center' | 'end';

const ALIGN_CLASS: Record<TextAlignValue, string> = {
  start: 'rc-align-start',
  center: 'rc-align-center',
  end: 'rc-align-end',
};

function alignFromClassList(classList: DOMTokenList): TextAlignValue | null {
  return (Object.keys(ALIGN_CLASS) as TextAlignValue[]).find(align => classList.contains(ALIGN_CLASS[align])) ?? null;
}

export const TextAlign = Extension.create({
  name: 'textAlign',

  addOptions() {
    return {
      types: ['heading', 'paragraph'],
    };
  },

  addGlobalAttributes() {
    return [
      {
        types: this.options.types,
        attributes: {
          align: {
            default: null,
            parseHTML: (element: HTMLElement) => alignFromClassList(element.classList),
            // Folded into the node's own `class` output via a render hook the node type
            // already owns (CustomHeading) — plain paragraphs get their class added
            // here since `<p>` has no custom render function of its own.
            renderHTML: (attributes: { align?: TextAlignValue | null }) => {
              if (!attributes.align || attributes.align === 'start') return {};

              return { class: ALIGN_CLASS[attributes.align] };
            },
          },
        },
      },
    ];
  },
});
