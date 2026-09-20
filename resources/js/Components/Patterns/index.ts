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
export { default as OverviewSection } from './OverviewSection.vue';
export { default as SectionHeading } from './SectionHeading.vue';
export { default as ShowPageGrid } from './ShowPageGrid.vue';
export { default as StatCard } from './StatCard.vue';
export { default as StatTile } from './StatTile.vue';
export { default as StatusBadge } from './StatusBadge.vue';
export { default as EmptyState } from './EmptyState.vue';
export { default as CollectionSkeleton } from './Skeletons/CollectionSkeleton.vue';
export { default as RecordSkeleton } from './Skeletons/RecordSkeleton.vue';
export { default as FormSkeleton } from './Skeletons/FormSkeleton.vue';
export { default as SectionCardSkeleton } from './Skeletons/SectionCardSkeleton.vue';
export { default as TopProgressBar } from './TopProgressBar.vue';
export { default as ConfirmDialog } from './ConfirmDialog.vue';
export { default as SheetForm } from './SheetForm.vue';
export { default as FormSection } from './FormSection.vue';

/**
 * Re-exported so callers have one import path for the whole pattern tier and
 * don't need to know SectionCard lives in ui/ for historical reasons.
 */
export { SectionCard } from '@/Components/ui/section-card';
