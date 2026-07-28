import { Mark, mergeAttributes } from '@tiptap/core';

/**
 * The dot-pill "tag" seen on MembershipPage.vue — a small dot + label, in two visual
 * variants: `filled` (a tinted rounded pill, e.g. the "Maskotė" badge) and `plain`
 * (dot + text with no background, e.g. "Aktyvi VU SA narė nuo 2003 m."). Implemented
 * as a *mark* (wraps a text selection), not a node, so it behaves like any other
 * inline formatting (bold, a link) rather than needing its own block insertion UI.
 *
 * The dot itself is a CSS `::before` on `.rc-tag` (see app.css) — not a real child
 * element — so the whole thing survives `HtmlSanitizerService` as a single
 * `<span class="rc-tag rc-tag-{variant} rc-tag-{color}">`, which already allowlists
 * `class` on `span`.
 */
export type RCTagVariant = 'filled' | 'plain';
export type RCTagColor = 'zinc' | 'red' | 'yellow' | 'green';

export interface RCTagOptions {
  HTMLAttributes: Record<string, any>;
}

declare module '@tiptap/core' {
  interface Commands<ReturnType> {
    rcTag: {
      setRCTag: (attributes: { variant: RCTagVariant; color: RCTagColor }) => ReturnType;
      unsetRCTag: () => ReturnType;
    };
  }
}

const VARIANTS: RCTagVariant[] = ['filled', 'plain'];
const COLORS: RCTagColor[] = ['zinc', 'red', 'yellow', 'green'];

function classListToVariant(classList: DOMTokenList): RCTagVariant | null {
  return VARIANTS.find(variant => classList.contains(`rc-tag-${variant}`)) ?? null;
}

function classListToColor(classList: DOMTokenList): RCTagColor | null {
  return COLORS.find(color => classList.contains(`rc-tag-${color}`)) ?? null;
}

export const RCTag = Mark.create<RCTagOptions>({
  name: 'rcTag',

  addOptions() {
    return {
      HTMLAttributes: {},
    };
  },

  addAttributes() {
    return {
      variant: {
        default: 'filled' as RCTagVariant,
        renderHTML: () => ({}), // folded into the single `class` string below
      },
      color: {
        default: 'yellow' as RCTagColor,
        renderHTML: () => ({}),
      },
    };
  },

  parseHTML() {
    return [
      {
        tag: 'span.rc-tag',
        getAttrs: (dom: HTMLElement) => ({
          variant: classListToVariant(dom.classList) ?? 'filled',
          color: classListToColor(dom.classList) ?? 'yellow',
        }),
      },
    ];
  },

  renderHTML({ HTMLAttributes, mark }) {
    const variant = mark.attrs.variant as RCTagVariant;
    const color = mark.attrs.color as RCTagColor;
    const classes = ['rc-tag', `rc-tag-${variant}`, `rc-tag-${color}`].join(' ');

    return ['span', mergeAttributes(this.options.HTMLAttributes, HTMLAttributes, { class: classes }), 0];
  },

  addCommands() {
    return {
      setRCTag: attributes => ({ commands }) => commands.setMark(this.name, attributes),
      unsetRCTag: () => ({ commands }) => commands.unsetMark(this.name),
    };
  },
});
