/**
 * Shared section-chrome class maps — background/padding/inner-width/rounding.
 * Extracted out of `RCSection.vue` so the new standalone `section` block
 * (`RCSection/SectionDisplay.vue`) and `HeroElement.vue` can reuse the exact same
 * background/padding/rounding vocabulary instead of re-declaring their own subset.
 */
export type SectionBackground = 'none' | 'muted' | 'contrast' | 'gradient';
export type SectionPadding = 'none' | 'sm' | 'md' | 'lg';
export type SectionInner = 'prose' | 'content' | 'wide' | 'full';
export type SectionRounded = 'none' | 'sm' | 'md' | 'lg';

/** Semantic heading level for a section title. Matches the levels offered in RCSectionOptions. */
export type SectionHeadingLevel = 2 | 3 | 4;

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

export const BACKGROUND_CLASS: Record<SectionBackground, string> = {
  none: '',
  muted: 'bg-zinc-50 dark:bg-zinc-900',
  contrast: 'bg-white dark:bg-zinc-950',
  // Same subtle surface as RichContentCard / the hero panel variant — for a section
  // that wants that card-like look rather than a flat fill.
  gradient: 'bg-gradient-to-br from-zinc-50 to-zinc-100/50 dark:from-zinc-800/80 dark:to-zinc-900',
};

export const PADDING_CLASS: Record<SectionPadding, string> = {
  none: '',
  sm: 'py-8',
  md: 'py-12',
  lg: 'py-16',
};

export const INNER_CLASS: Record<SectionInner, string> = {
  prose: 'max-w-2xl',
  content: 'max-w-4xl',
  wide: 'max-w-6xl',
  full: 'max-w-none',
};

/**
 * `overflow-hidden` is bundled with the radius — every current use (section
 * backgrounds, hero variants) rounds the same element that also clips a background
 * fill/gradient/decorative blur, so a rounded corner without clipping would just show
 * a square background peeking past the curve.
 */
export const ROUNDED_CLASS: Record<SectionRounded, string> = {
  none: '',
  sm: 'md:rounded-lg md:overflow-hidden',
  md: 'md:rounded-xl md:overflow-hidden',
  lg: 'md:rounded-2xl md:overflow-hidden',
};
