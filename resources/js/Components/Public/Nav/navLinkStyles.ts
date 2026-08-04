import type { NavItem, NavLink } from './types';

/**
 * Static Tailwind class lookup tables for the presentation attributes editors set in
 * the admin navigation builder (`extra_attributes.*`, hoisted onto the link by
 * `NavigationService::getNavigationForPublic()`).
 *
 * These MUST stay complete, literal class strings — Tailwind v4 extracts classes by
 * scanning source text, so a template string like `` `opacity-${n}` `` is invisible to
 * it and silently renders unstyled. This bit the old `` `lg:grid-cols-${item.cols}` ``
 * column count (see `colsClass` below), which only "worked" because the literal
 * classes happened to appear elsewhere in the tree.
 */

export const overlayClass: Record<NonNullable<NavLink['image_overlay']>, string> = {
  none: 'opacity-100',
  light: 'opacity-80',
  medium: 'opacity-60',
  heavy: 'opacity-40',
};

export const blurClass: Record<NonNullable<NavLink['image_blur']>, string> = {
  0: '',
  2: 'blur-[2px]',
  4: 'blur-[4px]',
  8: 'blur-[8px]',
};

export const gradientClass: Record<NonNullable<NavLink['image_gradient']>, string> = {
  none: '',
  bottom: 'bg-gradient-to-t from-black/60 via-black/20 to-transparent',
  full: 'bg-black/40',
};

export const colSpanClass: Record<NonNullable<NavLink['col_span']>, string> = {
  1: '',
  2: 'lg:col-span-2',
  3: 'lg:col-span-3',
};

/** `item.cols` (1–3) → the dropdown's grid column count. */
export const colsClass: Record<number, string> = {
  1: 'lg:grid-cols-1',
  2: 'lg:grid-cols-2',
  3: 'lg:grid-cols-3',
};

/**
 * `item.menu_width` → dropdown width. `wide` is the default and matches the menu's
 * original fixed width (650/800px) regardless of column count — a 1-column dropdown
 * like a plain link list looks identical to how it always has unless an editor
 * explicitly opts into a narrower or auto-scaled width.
 */
export const menuWidthClass: Record<NonNullable<NavItem['menu_width']>, string> = {
  narrow: 'max-lg:w-[340px] lg:w-[320px] xl:w-[360px]',
  medium: 'max-lg:w-[340px] lg:w-[500px] xl:w-[560px]',
  wide: 'max-lg:w-[340px] lg:w-[650px] xl:w-[800px]',
  auto: '',
};

/** Used only when `menu_width` is `auto` — scales with the actual column count. */
const autoDropdownWidthClass: Record<number, string> = {
  1: 'max-lg:w-[340px] lg:w-[320px] xl:w-[360px]',
  2: 'max-lg:w-[340px] lg:w-[500px] xl:w-[560px]',
  3: 'max-lg:w-[340px] lg:w-[650px] xl:w-[800px]',
};

// `badge_variant` needs no class map of its own — it's already a valid `Badge`
// `variant` prop value (rose/emerald/amber/sky/zinc; see Components/ui/badge/index.ts),
// so the template passes it straight through.

/** Defaults mirror `NavigationRequest`'s validation defaults and today's hardcoded look. */
export function resolveImageOverlay(link: NavLink): string {
  return overlayClass[link.image_overlay ?? 'medium'];
}

export function resolveImageBlur(link: NavLink): string {
  return blurClass[link.image_blur ?? 0];
}

export function resolveImageGradient(link: NavLink): string {
  return gradientClass[link.image_gradient ?? 'bottom'];
}

export function resolveColSpan(link: NavLink): string {
  return colSpanClass[link.col_span ?? 1];
}

export function resolveCols(cols: number | undefined): string {
  return colsClass[cols ?? 1] ?? colsClass[1];
}

export function resolveDropdownWidth(menuWidth: NavItem['menu_width'], cols: number | undefined): string {
  const width = menuWidth ?? 'wide';

  if (width === 'auto') {
    return autoDropdownWidthClass[cols ?? 1] ?? autoDropdownWidthClass[1];
  }

  return menuWidthClass[width];
}
