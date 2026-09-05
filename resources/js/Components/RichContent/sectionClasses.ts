/**
 * Shared section-chrome class maps. `SectionInner` still varies per-type (a section's
 * own inner content measure, independent of the canvas column it sits in); background/
 * padding/rounded/divider are no longer per-block choices — see `bandLayout.ts`, which
 * computes a band's ground automatically from its position in the document.
 */
export type SectionInner = 'prose' | 'content' | 'wide' | 'full';
export type PlainPadding = 'none' | 'compact' | 'default';

/** Semantic heading level for a section title. Matches the levels offered in RCSectionOptions. */
export type SectionHeadingLevel = 2 | 3 | 4;

/** A band's resolved ground: alternates canvas/tint automatically, or the one loud emphasis. */
export type BandTint = 'canvas' | 'tint' | 'emphasis';

/**
 * Size class per heading level — the same scale `.rc-prose` uses for h2/h3/h4
 * (app.css), so a section title marked as a given level renders at the same size as
 * an inline Tiptap heading of that level, rather than a one-off SectionHeader size.
 */
export const SECTION_HEADING_SIZE_CLASS: Record<SectionHeadingLevel, string> = {
  2: 'text-3xl',
  3: 'text-2xl',
  4: 'text-xl',
};

/** A band's fixed vertical rhythm — was `PADDING_CLASS.lg`, what 9 of 11 band types already used. */
export const BAND_PADDING = 'py-16';

/** The one exception: `hero`'s `banner` variant is a compact single-row strip. */
export const BAND_PADDING_COMPACT = 'py-8';

/** Optional rhythm for a plain section, which has no background to imply its own spacing. */
export const PLAIN_PADDING_CLASS: Record<PlainPadding, string> = {
  none: '',
  compact: 'py-8',
  default: BAND_PADDING,
};

/**
 * Two calm tints alternating down the page, plus one loud one. `tint` is exactly what
 * `EventCalendarElement.vue` has hardcoded since Phase 4 — this generalises it to every
 * band-capable block. Token-driven, not fixed zinc, so it follows whichever surface the
 * block is rendered on.
 */
export const BAND_GROUND_CLASS: Record<BandTint, string> = {
  canvas: '',
  tint: 'bg-secondary/40 border-y border-border',
  emphasis: 'bg-brand-fill text-brand-foreground border-y border-brand-fill',
};

export const INNER_CLASS: Record<SectionInner, string> = {
  prose: 'max-w-2xl',
  content: 'max-w-4xl',
  wide: 'max-w-6xl',
  full: 'max-w-none',
};
