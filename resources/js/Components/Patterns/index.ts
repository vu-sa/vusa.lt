/**
 * Generic, domain-free building blocks for admin pages.
 *
 * Everything exported here is safe to use anywhere: no `route()` calls, no
 * `App.Entities.*` types, no knowledge of a particular model. A component that
 * needs either of those belongs in an entity folder (`Duties/`, `Institutions/`,
 * …) where it can compose these instead.
 *
 * See `resources/js/Components/CLAUDE.md` for the tier rules and a
 * "what do I reach for" decision table.
 */

export { default as DateBadge } from './DateBadge.vue';
export { default as EntityLinkCard } from './EntityLinkCard.vue';
export { default as ShowPageGrid } from './ShowPageGrid.vue';
export { default as StatTile } from './StatTile.vue';

/**
 * Re-exported so callers have one import path for the whole pattern tier and
 * don't need to know these two live elsewhere for historical reasons.
 */
export { SectionCard } from '@/Components/ui/section-card';
export { default as EmptyState } from '@/Components/Empty/EmptyState.vue';
