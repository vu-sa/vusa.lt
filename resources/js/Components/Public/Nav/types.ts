/**
 * Shared shape of the `mainNavigation` Inertia prop, as built by
 * `App\Services\NavigationService::getNavigationForPublic()`. The tree is exactly two levels
 * deep: root items (`Item`) with children (`Link`) grouped into up to three columns.
 *
 * This is the single source of truth for the shape — `Types/models.d.ts`'s generated
 * `Navigation` interface lacks `links`/`cols` and should not be used for this prop.
 */
export type NavBadgeVariant = 'rose' | 'emerald' | 'amber' | 'sky' | 'zinc';

export interface NavLink {
  id?: number;
  name: string;
  url?: string;
  icon?: string;
  image?: string;
  description?: string;
  type?: 'link' | 'block-link' | 'category-link' | 'full-height-background-link' | 'divider' | 'heading';
  small_text?: string;
  badge_variant?: NavBadgeVariant;
  featured?: boolean;
  new_tab?: boolean;
  col_span?: 1 | 2 | 3;
  /** Brand eyebrow above an image card's headline. */
  eyebrow?: string;
  /** Call-to-action label under an image card's copy, e.g. "Registruokis". */
  cta?: string;
  /** `card` (default, full-bleed background) or `thumbnail` (small leading image). */
  image_render?: 'card' | 'thumbnail';
  /** Image-card height. `short` is the default; `tall` is for a column's single feature card. */
  image_height?: 'short' | 'tall';
  image_overlay?: 'none' | 'light' | 'medium' | 'heavy';
  image_blur?: 0 | 2 | 4 | 8;
  /** Raw CSS `object-position`, e.g. `"50% 30%"` — set via FocalPointPicker. */
  image_focal?: string;
  image_gradient?: 'none' | 'bottom' | 'full';
}

export type NavLinkType = Exclude<NavLink['type'], 'divider' | 'heading' | undefined>;

export interface NavItem {
  id: string;
  name: string;
  icon?: string;
  cols?: number;
  links: NavLink[][];
}
