import type { VisitOptions } from '@inertiajs/core';

/**
 * Only a visit that swaps the page gets a view transition. While one runs every press lands on
 * <html>, so a background save would dismiss whatever popover the user is clicking into.
 * `options.viewTransition` is ignored: `<Link>` always sends `false`, so it says nothing.
 */
export function wantsViewTransition(options: VisitOptions): boolean {
  return !options.preserveState
    && !options.async
    && !options.only?.length;
}
