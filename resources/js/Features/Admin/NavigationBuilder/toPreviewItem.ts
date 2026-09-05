import type { NavItem, NavLink } from '@/Components/Public/Nav/types';

import type { AdminNavigationLink, AdminNavigationRoot } from './types';

/**
 * Mirrors `NavigationService::getNavigationForPublic()`'s hoisting of `extra_attributes`
 * onto the link itself, plus its `is_active` filter — so the builder's "Peržiūra" mode
 * can render the exact same `MainNavigationMenuContent` component the public site uses,
 * fed from the live (possibly unsaved) builder state instead of a server round-trip.
 */
function toPreviewLink(link: AdminNavigationLink): NavLink {
  const extra = link.extra_attributes ?? {};

  return {
    id: link.id,
    name: link.name ?? '',
    url: link.url,
    ...extra,
  };
}

export function toPreviewItem(root: AdminNavigationRoot): NavItem {
  const links = root.links.map(column => column
    .filter(link => link.is_active)
    .map(toPreviewLink));

  const nonEmptyColumns = links.filter(column => column.length > 0);

  return {
    id: String(root.id),
    name: root.name ?? '',
    icon: root.extra_attributes?.icon,
    cols: nonEmptyColumns.length,
    links: nonEmptyColumns,
  };
}
