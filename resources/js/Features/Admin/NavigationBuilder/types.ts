import type { NavBadgeVariant } from '@/Components/Public/Nav/types';

/**
 * The raw shape returned by `NavigationService::getTreeForAdmin()` — unlike the public
 * `NavItem`/`NavLink` shape (`Components/Public/Nav/types.ts`), `extra_attributes` stays
 * nested (not hoisted to top-level keys) and every column is present, even empty ones,
 * so the builder always has exactly 3 drop targets per root.
 */
export interface AdminNavigationExtraAttributes {
  type?: 'link' | 'block-link' | 'category-link' | 'full-height-background-link' | 'divider' | 'heading';
  column?: 1 | 2 | 3;
  col_span?: 1 | 2 | 3;
  cols?: 1 | 2 | 3;
  /** Root items only — dropdown width, independent of column count. Defaults to `wide`. */
  icon?: string;
  description?: string;
  small_text?: string;
  badge_variant?: NavBadgeVariant;
  featured?: boolean;
  new_tab?: boolean;
  image?: string;
  image_render?: 'card' | 'thumbnail';
  image_overlay?: 'none' | 'light' | 'medium' | 'heavy';
  image_blur?: 0 | 2 | 4 | 8;
  image_focal?: string;
  image_gradient?: 'none' | 'bottom' | 'full';
  [key: string]: unknown;
}

export interface AdminNavigationLink {
  id: number;
  name: string | null;
  url: string;
  parent_id: number;
  lang: string;
  order: number;
  is_active: boolean;
  extra_attributes: AdminNavigationExtraAttributes | null;
  deleted_at?: string | null;
  [key: string]: unknown;
}

export interface AdminNavigationRoot extends AdminNavigationLink {
  /** Always exactly 3 arrays — array index 0/1/2 is column 1/2/3. */
  links: AdminNavigationLink[][];
  cols: number;
}

export interface TranslationSummaryMismatch {
  order: number;
  lt_children: number | null;
  en_children: number | null;
}

export interface TranslationSummary {
  counts: { lt: number; en: number };
  mismatchedRoots: TranslationSummaryMismatch[];
}
