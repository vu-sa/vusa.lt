import type { Hero } from '@/Types/contentParts';

export type HeroVariant = NonNullable<Hero['options']>['variant'];

/**
 * Per-variant class strings shared between `HeroElement.vue` (public display) and
 * `HeroEditableElement.vue` (full-screen editor) — a single source of truth so the two
 * can never drift into different font sizes/spacing for the same variant.
 */
export function heroTitleClass(variant: HeroVariant): string {
  switch (variant) {
    case 'centered':
      return 'rc-hero-title u-display uppercase mt-3 text-3xl text-foreground sm:text-4xl md:text-5xl';
    case 'banner':
      return 'rc-hero-title u-display uppercase text-lg text-foreground sm:text-xl md:text-2xl';
    case 'panel':
      return 'rc-hero-title u-display uppercase mt-2 text-2xl text-foreground sm:text-3xl';
    default:
      return 'rc-hero-title u-display uppercase text-3xl text-foreground sm:text-4xl md:text-5xl lg:text-6xl';
  }
}

export function heroDescriptionClass(variant: HeroVariant): string {
  switch (variant) {
    case 'centered':
      return 'mt-4 text-sm leading-relaxed text-muted-foreground sm:text-base md:text-lg';
    case 'panel':
      return 'mt-2 max-w-prose text-sm leading-6 text-muted-foreground sm:text-base';
    default:
      return 'max-w-lg text-sm leading-relaxed text-muted-foreground sm:text-base md:text-lg lg:text-xl';
  }
}

export function heroTitleAlignmentClass(variant: HeroVariant): string {
  if (variant === 'centered') return 'text-center';
  if (variant === 'banner') return 'text-center sm:text-left';
  return 'text-left';
}

export function heroButtonsClass(variant: HeroVariant): string | undefined {
  if (variant === 'centered') return 'mt-6 justify-center';
  if (variant === 'panel') return 'mt-4';
  return undefined;
}
