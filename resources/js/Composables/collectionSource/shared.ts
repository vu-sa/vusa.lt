import { shallowReactive } from 'vue';


/** "Rodyti daugiau" loads this many at a time (.ai/rules/js-pages-admin.md). */
export const PAGE_SIZE = 50;

/** Bounds the replay of `?pages=`, as in the Typesense source. */
export const MAX_RESTORED_PAGES = 10;

/**
 * Optimistic changes on top of whatever the source loaded. Kept for the page's lifetime: once
 * the request succeeds the patch is the server's truth, and a lagging index must not undo it.
 */
export function useOptimisticOverlay<T>() {
  const patches = shallowReactive(new Map<symbol, { ids: Set<string>; patch: Partial<T> }>());
  const hidden = shallowReactive(new Map<symbol, Set<string>>());
  const keyOf = (item: T) => String((item as { id?: unknown }).id);

  function apply(items: readonly T[]): T[] {
    if (patches.size === 0 && hidden.size === 0) {
      return items as T[];
    }

    return items
      .filter(item => ![...hidden.values()].some(ids => ids.has(keyOf(item))))
      .map((item) => {
        let result = item;
        for (const { ids, patch } of patches.values()) {
          if (ids.has(keyOf(item))) result = { ...result, ...patch };
        }
        return result;
      });
  }

  function patchItems(ids: string[], patch: Partial<T>): () => void {
    const token = Symbol();
    patches.set(token, { ids: new Set(ids), patch: { ...patch } });
    return () => { patches.delete(token); };
  }

  function hideItems(ids: string[]): () => void {
    const token = Symbol();
    hidden.set(token, new Set(ids));
    return () => { hidden.delete(token); };
  }

  return { apply, patchItems, hideItems };
}
