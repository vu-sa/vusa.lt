import { markRaw } from 'vue';

import { getSkeletonForType } from './Types';
import { Skeleton } from '@/Components/ui/skeleton';

/**
 * Build the Suspense fallback component for a type from its registry skeleton.
 * Cached at module scope (not per-component-instance) so repeated blocks of the same
 * type across the whole page — not just within one `RichContentBlock` — share one
 * component definition. Extracted out of `RichContentParser.vue` so `RichContentBlock`
 * can use the same cache when a `section` block renders its children through it.
 */
const skeletonCache = new Map<string, { components: { Skeleton: typeof Skeleton }; template: string }>();

export function getSkeletonComponent(type: string) {
  let cached = skeletonCache.get(type);
  if (!cached) {
    cached = markRaw({ components: { Skeleton }, template: getSkeletonForType(type).template });
    skeletonCache.set(type, cached);
  }
  return cached;
}
