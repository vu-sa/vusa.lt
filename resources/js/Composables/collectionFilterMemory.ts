import { usePage } from '@inertiajs/vue3';

/**
 * Collection pages remember their filters and sort per page and per user, so coming back to
 * Posėdžiai finds the list as it was left. The URL stays the source of truth: remembered params
 * are written back into it before a source reads it, and every URL sync is remembered.
 */
const PREFIX = 'collection-filters:';

function currentUserId(): string {
  try {
    const id = (usePage().props as { auth?: { user?: { id?: unknown } } }).auth?.user?.id;

    return id === undefined || id === null ? 'guest' : String(id);
  }
  catch {
    return 'guest';
  }
}

/** Trash is a separate view over the same page; it is neither restored nor remembered. */
const isTrash = (params: URLSearchParams) => params.get('showDeleted') === 'true';

export interface CollectionFilterMemory {
  /**
   * Puts the page's remembered params back into the URL when it carries none of its own. A link
   * with filters, or a search (`q` / `search`), is an explicit ask and wins over the memory.
   *
   * `decided`: the URL or a remembered state (a cleared one included) fixes the list, so a source
   * applies no defaults. `restored`: the URL changed, so a server-rendered first page is stale.
   */
  restore: () => { decided: boolean; restored: boolean };
  /** Remembers the page's filter params from the URL it just wrote — an empty list included. */
  remember: () => void;
}

/** Call during setup: the key (user + page) is fixed then, not when a filter changes later. */
export function useCollectionFilterMemory(filterKeys: string[], queryKeys: string[] = ['q', 'search']): CollectionFilterMemory {
  const storageKey = `${PREFIX}${currentUserId()}:${window.location.pathname}`;

  function restore(): { decided: boolean; restored: boolean } {
    const params = new URLSearchParams(window.location.search);

    if (isTrash(params) || [...filterKeys, ...queryKeys].some(key => params.has(key))) {
      return { decided: true, restored: false };
    }

    const remembered = localStorage.getItem(storageKey);

    if (remembered === null) {
      return { decided: false, restored: false };
    }

    const url = new URL(window.location.href);
    let restored = false;
    new URLSearchParams(remembered).forEach((value, key) => {
      if (filterKeys.includes(key)) {
        url.searchParams.set(key, value);
        restored = true;
      }
    });

    if (restored) {
      window.history.replaceState(window.history.state, '', url.toString());
    }

    return { decided: true, restored };
  }

  function remember(): void {
    const params = new URLSearchParams(window.location.search);

    if (isTrash(params)) {
      return;
    }

    const remembered = new URLSearchParams();
    for (const key of filterKeys) {
      const value = params.get(key);
      if (value !== null) {
        remembered.set(key, value);
      }
    }

    localStorage.setItem(storageKey, remembered.toString());
  }

  return { restore, remember };
}
