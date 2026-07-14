/**
 * useUIPreferences - Single source of truth for admin sidebar customization
 * (section visibility + order) and recently visited pages.
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
 * const { isSectionVisible, recentPages } = useUIPreferences()
 */

import {
  ref, reactive, computed, provide, inject,
  type ComputedRef, type InjectionKey, type Ref,
} from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { resolveCatalogEntryByRoute } from '@/Composables/adminPageCatalog';
import type { RecentItem } from '@/Composables/useCommandPalette';

/** Toggleable section keys — must match HasUIPreferences::$toggleableSidebarSections */
export const TOGGLEABLE_SECTIONS = [
  'pinned',
  'quick_actions',
  'recently_visited',
  'followed_institutions',
  'spacer',
  'start_fm',
  'secondary',
] as const;

export type ToggleableSection = typeof TOGGLEABLE_SECTIONS[number];

export type SidebarDensity = 'comfortable' | 'compact';

/** Toggleable quick-action keys — must match HasUIPreferences::$toggleableQuickActions */
export const QUICK_ACTION_KEYS = [
  'new_problem',
  'new_meeting',
  'new_news',
  'new_reservation',
  'duty_update',
] as const;

export type QuickActionKey = typeof QUICK_ACTION_KEYS[number];

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
  sectionVisibility: Record<ToggleableSection, boolean>;
  isSectionVisible: (key: ToggleableSection) => boolean;
  setSectionVisibility: (key: ToggleableSection, value: boolean) => void;
  /** Section keys in the user's chosen order */
  orderedSections: ComputedRef<ToggleableSection[]>;
  setSectionOrder: (keys: ToggleableSection[]) => void;
  resetSections: () => void;
  quickActionVisibility: Record<QuickActionKey, boolean>;
  isQuickActionVisible: (key: QuickActionKey) => boolean;
  setQuickActionVisibility: (key: QuickActionKey, value: boolean) => void;
  recentPages: ComputedRef<RecentItem[]>;
  trackVisit: (routeName: string, params?: Record<string, unknown>, title?: string, url?: string) => void;
  clearRecent: () => void;
  /** Pinned (favorited) pages, in user order */
  pinnedPages: ComputedRef<RecentItem[]>;
  isPinned: (item: { routeName?: string; href?: string }) => boolean;
  togglePin: (item: { routeName?: string; href?: string; title?: string }) => void;
  /** Sidebar density preference */
  density: Ref<SidebarDensity>;
  setDensity: (value: SidebarDensity) => void;
  /** Whether the sidebar is collapsed (icon-only) */
  sidebarCollapsed: Ref<boolean>;
  setSidebarCollapsed: (value: boolean) => void;
}

const UI_PREFERENCES_INJECTION_KEY: InjectionKey<UIPreferencesContext> = Symbol('ui-preferences');

const MAX_RECENT = 15;
const MAX_PINNED = 10;

interface ServerPrefs {
  sections: Record<string, boolean>;
  order: string[];
  collapsed: boolean;
  quickActions: Record<string, boolean>;
  density: SidebarDensity;
  pinned: StoredPinnedPage[];
  recent: StoredRecentPage[];
}

function readServerPrefs(): ServerPrefs {
  const page = usePage();
  const prefs = (page.props.auth as { user?: { ui_preferences?: unknown } })?.user?.ui_preferences as
    | {
      sidebar?: { sections?: Record<string, boolean>; order?: string[]; collapsed?: boolean };
      quick_actions?: Record<string, boolean>;
      appearance?: { density?: string };
      pinned_pages?: StoredPinnedPage[];
      recent_pages?: StoredRecentPage[];
    }
    | undefined;

  return {
    sections: prefs?.sidebar?.sections ?? {},
    order: prefs?.sidebar?.order ?? [],
    collapsed: prefs?.sidebar?.collapsed ?? false,
    quickActions: prefs?.quick_actions ?? {},
    density: prefs?.appearance?.density === 'compact' ? 'compact' : 'comfortable',
    pinned: prefs?.pinned_pages ?? [],
    recent: prefs?.recent_pages ?? [],
  };
}

/**
 * Sanitize a stored order to the known toggleable sections (deduped), then weave
 * in any missing sections at their *canonical* position (the order in
 * TOGGLEABLE_SECTIONS) rather than appending them at the end. This way a newly
 * introduced section (e.g. `pinned`) lands where it belongs for existing users,
 * while the relative order the user actually chose for present sections is kept.
 */
function sanitizeOrder(stored: string[]): ToggleableSection[] {
  const seen = new Set<string>();
  const result: ToggleableSection[] = [];

  for (const k of stored) {
    if ((TOGGLEABLE_SECTIONS as readonly string[]).includes(k) && !seen.has(k)) {
      seen.add(k);
      result.push(k as ToggleableSection);
    }
  }

  const canonicalIndex = (s: ToggleableSection) => TOGGLEABLE_SECTIONS.indexOf(s);

  for (const section of TOGGLEABLE_SECTIONS) {
    if (seen.has(section)) {
      continue;
    }
    // Insert before the first present item that sits later in canonical order.
    let insertAt = result.findIndex(s => canonicalIndex(s) > canonicalIndex(section));
    if (insertAt === -1) {
      insertAt = result.length;
    }
    result.splice(insertAt, 0, section);
    seen.add(section);
  }

  return result;
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

  // Section visibility — default missing keys to visible.
  const sectionVisibility = reactive(
    Object.fromEntries(
      TOGGLEABLE_SECTIONS.map(key => [key, server.sections[key] !== false]),
    ) as Record<ToggleableSection, boolean>,
  );

  // Section order — sanitized, always contains every toggleable section.
  const sectionOrder = ref<ToggleableSection[]>(sanitizeOrder(server.order));

  // Quick-action visibility — default missing keys to visible.
  const quickActionVisibility = reactive(
    Object.fromEntries(
      QUICK_ACTION_KEYS.map(key => [key, server.quickActions[key] !== false]),
    ) as Record<QuickActionKey, boolean>,
  );

  // Recently visited — local mirror, seeded from server, kept in sync optimistically.
  const recentRaw = ref<StoredRecentPage[]>([...server.recent]);

  // Pinned pages — local mirror, seeded from server, kept in sync optimistically.
  const pinnedRaw = ref<StoredPinnedPage[]>([...server.pinned]);

  // Sidebar density + collapsed state.
  const density = ref<SidebarDensity>(server.density);
  const sidebarCollapsed = ref<boolean>(server.collapsed);

  const isSectionVisible = (key: ToggleableSection) => sectionVisibility[key] !== false;

  const persistSections = () => {
    persist('api.v1.admin.user-preferences.update', {
      sidebar: {
        sections: { ...sectionVisibility },
        order: [...sectionOrder.value],
      },
      quick_actions: { ...quickActionVisibility },
    });
  };

  const setSectionVisibility = (key: ToggleableSection, value: boolean) => {
    if (!TOGGLEABLE_SECTIONS.includes(key)) {
      return;
    }
    sectionVisibility[key] = value;
    persistSections();
  };

  const orderedSections = computed<ToggleableSection[]>(() => sectionOrder.value);

  const setSectionOrder = (keys: ToggleableSection[]) => {
    sectionOrder.value = sanitizeOrder(keys);
    persistSections();
  };

  const resetSections = () => {
    TOGGLEABLE_SECTIONS.forEach((key) => {
      sectionVisibility[key] = true;
    });
    sectionOrder.value = [...TOGGLEABLE_SECTIONS];
    QUICK_ACTION_KEYS.forEach((key) => {
      quickActionVisibility[key] = true;
    });
    persistSections();
  };

  const isQuickActionVisible = (key: QuickActionKey) => quickActionVisibility[key] !== false;

  const setQuickActionVisibility = (key: QuickActionKey, value: boolean) => {
    if (!QUICK_ACTION_KEYS.includes(key)) {
      return;
    }
    quickActionVisibility[key] = value;
    persistSections();
  };

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

  const setDensity = (value: SidebarDensity) => {
    density.value = value;
    persist('api.v1.admin.user-preferences.update', { appearance: { density: value } });
  };

  const setSidebarCollapsed = (value: boolean) => {
    sidebarCollapsed.value = value;
    persist('api.v1.admin.user-preferences.update', { sidebar: { collapsed: value } });
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

    // Identity is the path when known (query string excluded, so the same
    // page is never stored twice); otherwise route+params.
    const identity = (e: StoredRecentPage) =>
      e.url ?? `${e.route}|${JSON.stringify(e.params ?? {})}`;
    const newIdentity = url ?? `${routeName}|${JSON.stringify(params)}`;

    // Optimistic local update (instant feedback in sidebar + palette).
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
    sectionVisibility,
    isSectionVisible,
    setSectionVisibility,
    orderedSections,
    setSectionOrder,
    resetSections,
    quickActionVisibility,
    isQuickActionVisible,
    setQuickActionVisibility,
    recentPages,
    trackVisit,
    clearRecent,
    pinnedPages,
    isPinned,
    togglePin,
    density,
    setDensity,
    sidebarCollapsed,
    setSidebarCollapsed,
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
    const sectionVisibility = reactive(
      Object.fromEntries(TOGGLEABLE_SECTIONS.map(key => [key, true])) as Record<ToggleableSection, boolean>,
    );
    const quickActionVisibility = reactive(
      Object.fromEntries(QUICK_ACTION_KEYS.map(key => [key, true])) as Record<QuickActionKey, boolean>,
    );

    return {
      sectionVisibility,
      isSectionVisible: () => true,
      setSectionVisibility: noop,
      orderedSections: computed(() => [...TOGGLEABLE_SECTIONS]) as ComputedRef<ToggleableSection[]>,
      setSectionOrder: noop,
      resetSections: noop,
      quickActionVisibility,
      isQuickActionVisible: () => true,
      setQuickActionVisibility: noop,
      recentPages: computed(() => []) as ComputedRef<RecentItem[]>,
      trackVisit: noop,
      clearRecent: noop,
      pinnedPages: computed(() => []) as ComputedRef<RecentItem[]>,
      isPinned: () => false,
      togglePin: noop,
      density: ref<SidebarDensity>('comfortable'),
      setDensity: noop,
      sidebarCollapsed: ref(false),
      setSidebarCollapsed: noop,
    };
  }

  return context;
}
