import { onUnmounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

/**
 * Optional (`Inertia::optional()`) props a record page loads per panel. A mutation's redirect would
 * otherwise reload the page without them, so every mutating visit is made partial: unloaded panels
 * stay excluded and loaded ones are requested again. `load()` fetches panels on demand.
 */
export function useOptionalPanelProps<K extends string>(options: {
  keys: readonly K[];
  isLoaded: (key: K) => boolean;
  /** Props a mutation never changes, kept out of every mutating reload. */
  alwaysExcept?: readonly string[];
  /** Read-only pages have no optional panels to load. */
  disabled?: () => boolean;
  /** Clears in-flight bookkeeping when the page shows another record. */
  recordKey?: () => unknown;
}) {
  const requested = new Set<K>();

  const stop = router.on('before', ({ detail: { visit } }) => {
    if (visit.method === 'get' || visit.only.length) return;

    visit.except = [...new Set([
      ...visit.except,
      ...options.keys.filter(key => !options.isLoaded(key)),
      ...(options.alwaysExcept ?? []),
    ])];
    // Nothing excluded: list every prop, so the request stays partial and loaded panels come back.
    if (!visit.except.length) {
      visit.only = Object.keys(usePage().props);
    }
  });
  onUnmounted(stop);

  if (options.recordKey) {
    watch(options.recordKey, () => requested.clear());
  }

  function load(keys: K[]): void {
    if (options.disabled?.()) return;
    const only = keys.filter(key => !options.isLoaded(key) && !requested.has(key));
    if (!only.length) return;
    only.forEach(key => requested.add(key));
    router.reload({
      only,
      onFinish: () => only.forEach(key => requested.delete(key)),
    });
  }

  return { load };
}
