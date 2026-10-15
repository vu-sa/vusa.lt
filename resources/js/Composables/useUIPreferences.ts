/**
 * useUIPreferences - Single source of truth for the admin's pinned and recently
 * visited pages.
 *
 * Server-backed (users.ui_preferences JSON column, shared via
 * HandleInertiaRequests as auth.user.ui_preferences). Provide/inject pattern,
 * following useCommandPalette.ts.
 *
 * Writes are side-effects, not navigations, so they go through a plain
 * `fetch` (the endpoints return 204) — using Inertia's router here would
 * break, since Inertia rejects non-Inertia responses.
 *
 * @example
 * // In AdminLayout.vue (provider):
 * const ui = createUIPreferencesProvider()
 *
 * // In any component (consumer):
 * const { pinnedPages, recentPages } = useUIPreferences()
 */

import {
  ref, computed, provide, inject,
  type ComputedRef, type InjectionKey,
} from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { resolveCatalogEntryByRoute } from '@/Composables/adminPageCatalog';
import type { RecentItem } from '@/Composables/useCommandPalette';

interface StoredRecentPage {
  route: string;
  params?: Record<string, unknown>;
  title?: string;
  url?: string;
  visited_at: string;
}

interface StoredPinnedPage {
  route: string;
  params?: Record<string, unknown>;
  title?: string | null;
  url?: string | null;
}

interface UIPreferencesContext {
  recentPages: ComputedRef<RecentItem[]>;
  trackVisit: (routeName: string, params?: Record<string, unknown>, title?: string, url?: string) => void;
  clearRecent: () => void;
  /** Pinned (favorited) pages, in user order */
  pinnedPages: ComputedRef<RecentItem[]>;
  isPinned: (item: { routeName?: string; href?: string }) => boolean;
  togglePin: (item: { routeName?: string; href?: string; title?: string }) => void;
}

const UI_PREFERENCES_INJECTION_KEY: InjectionKey<UIPreferencesContext> = Symbol('ui-preferences');

const MAX_RECENT = 15;
const MAX_PINNED = 10;

interface ServerPrefs {
  pinned: StoredPinnedPage[];
  recent: StoredRecentPage[];
}

function readServerPrefs(): ServerPrefs {
  const page = usePage();
  const prefs = (page.props.auth as { user?: { ui_preferences?: unknown } })?.user?.ui_preferences as
    | {
      pinned_pages?: StoredPinnedPage[];
      recent_pages?: StoredRecentPage[];
    }
    | undefined;

  return {
    pinned: prefs?.pinned_pages ?? [],
    recent: prefs?.recent_pages ?? [],
  };
}

/** Last-resort human label from a route name, e.g. "users.edit" → "Users edit". */
function prettifyRoute(routeName: string): string {
  const last = routeName.split('.').slice(-2).join(' ').replace(/[._-]+/g, ' ').trim();
  return last.charAt(0).toUpperCase() + last.slice(1);
}

interface StoredPageLike {
  route: string;
  params?: Record<string, unknown>;
  title?: string | null;
  url?: string | null;
  visited_at?: string;
}

/**
 * Map stored page entries (recent or pinned) to navigable `RecentItem`s,
 * resolving titles/icons from the catalog and skipping entries whose URL
 * can't be resolved. Shared by `recentPages` and `pinnedPages`.
 */
function mapStoredPages(stored: StoredPageLike[]): RecentItem[] {
  const items: RecentItem[] = [];

  for (const entry of stored) {
    const catalogEntry = resolveCatalogEntryByRoute(entry.route);

    // Prefer the title captured at store time; fall back to the catalog
    // label (clean i18n string) and finally the route name.
    const title = entry.title
      || (catalogEntry ? $t(catalogEntry.labelKey) : prettifyRoute(entry.route));

    let href: string | undefined = entry.url ?? undefined;
    if (!href) {
      try {
        href = route(entry.route, entry.params ?? {});
      }
      catch {
        href = undefined;
      }
    }

    if (!href) {
      continue;
    }

    items.push({
      // The path (no query string) is the page identity, so the same
      // page never duplicates and query params don't split it.
      id: href,
      type: 'page',
      title,
      href,
      routeName: entry.route,
      timestamp: entry.visited_at ? (Date.parse(entry.visited_at) || Date.now()) : Date.now(),
    });
  }

  return items;
}

function csrfToken(): string {
  return ((usePage().props as { csrf_token?: string }).csrf_token) ?? '';
}

/** Fire-and-forget JSON write to a 204 endpoint (no Inertia navigation). */
function persist(routeName: string, body: Record<string, unknown>): void {
  if (typeof window === 'undefined') {
    return;
  }
  void fetch(route(routeName), {
    method: 'PATCH',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: JSON.stringify(body),
  }).catch(() => {
    // Best-effort: a failed preference write should never disrupt the UI.
  });
}

/**
 * Creates the UI preferences provider context (call in AdminLayout).
 */
export function createUIPreferencesProvider(): UIPreferencesContext {
  const server = readServerPrefs();

  // Recently visited — local mirror, seeded from server, kept in sync optimistically.
  const recentRaw = ref<StoredRecentPage[]>([...server.recent]);

  // Pinned pages — local mirror, seeded from server, kept in sync optimistically.
  const pinnedRaw = ref<StoredPinnedPage[]>([...server.pinned]);

  const recentPages = computed<RecentItem[]>(() => mapStoredPages(recentRaw.value));

  const pinnedPages = computed<RecentItem[]>(() => mapStoredPages(pinnedRaw.value));

  const pinnedIdentity = (e: StoredPinnedPage) =>
    e.url ?? `${e.route}|${JSON.stringify(e.params ?? {})}`;

  const isPinned = (item: { routeName?: string; href?: string }): boolean => {
    if (!item.routeName && !item.href) {
      return false;
    }
    const target = item.href ?? `${item.routeName}|${JSON.stringify({})}`;
    return pinnedRaw.value.some((e) => {
      if (item.href && e.url) {
        return e.url === item.href;
      }
      return pinnedIdentity(e) === target;
    });
  };

  const persistPinned = () => {
    persist('api.v1.admin.user-preferences.update', { pinned_pages: [...pinnedRaw.value] });
  };

  const togglePin = (item: { routeName?: string; href?: string; title?: string }) => {
    if (!item.routeName) {
      return;
    }

    const already = isPinned(item);
    if (already) {
      pinnedRaw.value = pinnedRaw.value.filter((e) => {
        if (item.href && e.url) {
          return e.url !== item.href;
        }
        return pinnedIdentity(e) !== (item.href ?? `${item.routeName}|${JSON.stringify({})}`);
      });
    }
    else {
      pinnedRaw.value = [
        { route: item.routeName, params: {}, title: item.title ?? null, url: item.href ?? null },
        ...pinnedRaw.value,
      ].slice(0, MAX_PINNED);
    }

    persistPinned();
  };

  const trackVisit = (
    routeName: string,
    params: Record<string, unknown> = {},
    title?: string,
    url?: string,
  ) => {
    if (typeof window === 'undefined' || !routeName) {
      return;
    }

    // Never record the public catch-all 'page' route for admin paths
    if (routeName === 'page' && (url?.startsWith('/mano') || window.location.pathname.startsWith('/mano'))) {
      return;
    }

    // Identity is the path when known (query string excluded, so the same
    // page is never stored twice); otherwise route+params.
    const identity = (e: StoredRecentPage) =>
      e.url ?? `${e.route}|${JSON.stringify(e.params ?? {})}`;
    const newIdentity = url ?? `${routeName}|${JSON.stringify(params)}`;

    // Optimistic local update (instant feedback in the palette).
    recentRaw.value = [
      { route: routeName, params, title, url, visited_at: new Date().toISOString() },
      ...recentRaw.value.filter(e => identity(e) !== newIdentity),
    ].slice(0, MAX_RECENT);

    // One small request per real navigation — persisted immediately so it
    // survives a full page load.
    persist('api.v1.admin.user-preferences.trackRecentPage', { route: routeName, params, title, url });
  };

  const clearRecent = () => {
    recentRaw.value = [];
    persist('api.v1.admin.user-preferences.trackRecentPage', { clear: true });
  };

  const context: UIPreferencesContext = {
    recentPages,
    trackVisit,
    clearRecent,
    pinnedPages,
    isPinned,
    togglePin,
  };

  provide(UI_PREFERENCES_INJECTION_KEY, context);

  return context;
}

/**
 * Use the UI preferences from any component.
 * Falls back gracefully (no-op) if no provider exists.
 */
export function useUIPreferences(): UIPreferencesContext {
  const context = inject(UI_PREFERENCES_INJECTION_KEY, null);

  if (!context) {
    if (import.meta.env.DEV) {
      console.warn('useUIPreferences: No provider found. Make sure AdminLayout uses createUIPreferencesProvider.');
    }

    const noop = () => {};

    return {
      recentPages: computed(() => []) as ComputedRef<RecentItem[]>,
      trackVisit: noop,
      clearRecent: noop,
      pinnedPages: computed(() => []) as ComputedRef<RecentItem[]>,
      isPinned: () => false,
      togglePin: noop,
    };
  }

  return context;
}
