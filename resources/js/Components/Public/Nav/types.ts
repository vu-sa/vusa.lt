/**
 * Shared shape of the `mainNavigation` Inertia prop, as built by
 * `App\Services\NavigationService::getNavigationForPublic()`. The tree is exactly two levels
 * deep: root items (`Item`) with children (`Link`) grouped into up to three columns.
 *
 * This is the single source of truth for the shape — `Types/models.d.ts`'s generated
 * `Navigation` interface lacks `links`/`cols` and should not be used for this prop.
 */
export interface NavLink {
  name: string;
  url?: string;
  icon?: string;
  image?: string;
  description?: string;
  type?: 'link' | 'block-link' | 'category-link' | 'full-height-background-link' | 'divider';
  small_text?: string;
}

export type NavLinkType = Exclude<NavLink['type'], 'divider' | undefined>;

export interface NavItem {
  id: string;
  cols?: number;
  links: NavLink[][];
}
