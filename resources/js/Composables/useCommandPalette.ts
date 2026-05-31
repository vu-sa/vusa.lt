/**
 * useCommandPalette - Global state management for the admin command palette
 *
 * Uses provide/inject pattern (following useTourProvider.ts) to allow the command
 * palette to be controlled from anywhere in the admin interface.
 *
 * @example
 * // In AdminLayout.vue (provider):
 * const commandPalette = createCommandPaletteProvider()
 *
 * // In any component (consumer):
 * const { open, toggle } = useCommandPalette()
 */

import { ref, computed, provide, inject, readonly, onMounted, onUnmounted, type ComputedRef, type InjectionKey, type Ref } from 'vue';

// Types
export interface RecentItem {
  id: string;
  type: 'meeting' | 'agenda_item' | 'action' | 'news' | 'page' | 'calendar' | 'institution' | 'document';
  title: string;
  href?: string;
  /** Ziggy route name, when the item is an admin page (used for icon lookup) */
  routeName?: string;
  timestamp: number;
}

/**
 * Source of recently visited pages. Wired to useUIPreferences (server-backed)
 * so the command palette "Neseniai" list and the sidebar share one source.
 */
export interface RecentSource {
  recentPages: ComputedRef<RecentItem[]> | Ref<RecentItem[]>;
  clearRecent: () => void;
}

interface CommandPaletteContext {
  /** Whether the command palette is currently open */
  isOpen: Ref<boolean>;
  /** Current search query */
  query: Ref<string>;
  /** Recently visited pages (server-backed via useUIPreferences) */
  recentItems: Ref<RecentItem[]>;
  /** Open the command palette */
  open: () => void;
  /** Close the command palette */
  close: () => void;
  /** Toggle the command palette */
  toggle: () => void;
  /** Add an item to recent history */
  addRecentItem: (item: Omit<RecentItem, 'timestamp'>) => void;
  /** Clear recent items */
  clearRecentItems: () => void;
}

const COMMAND_PALETTE_INJECTION_KEY: InjectionKey<CommandPaletteContext> = Symbol('command-palette');

/**
 * Creates the command palette provider context (call in AdminLayout).
 *
 * Pass the useUIPreferences context as `recentSource` so the palette's
 * "Neseniai" list reflects the same server-backed recently-visited pages as
 * the sidebar. (Provide/inject can't resolve a sibling provider during the
 * same setup, so the source is passed explicitly.)
 */
export function createCommandPaletteProvider(recentSource?: RecentSource): CommandPaletteContext {
  const isOpen = ref(false);
  const query = ref('');
  const fallbackRecent = ref<RecentItem[]>([]);
  const recentItems = computed<RecentItem[]>(
    () => recentSource?.recentPages.value ?? fallbackRecent.value,
  ) as unknown as Ref<RecentItem[]>;

  const open = () => {
    isOpen.value = true;
    query.value = '';
  };

  const close = () => {
    isOpen.value = false;
    query.value = '';
  };

  const toggle = () => {
    if (isOpen.value) {
      close();
    }
    else {
      open();
    }
  };

  // Recently visited is now driven by the global visit tracker
  // (useUIPreferences). Kept as a no-op shim so existing callers
  // (ActionResult, NewsResult, …) don't break.
  const addRecentItem = (_item: Omit<RecentItem, 'timestamp'>) => {};

  const clearRecentItems = () => {
    if (recentSource) {
      recentSource.clearRecent();
    }
    else {
      fallbackRecent.value = [];
    }
  };

  // Register global keyboard shortcut
  const handleKeydown = (event: KeyboardEvent) => {
    // Cmd+K (Mac) or Ctrl+K (Windows/Linux)
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
      event.preventDefault();
      toggle();
    }

    // Escape to close (handled by dialog, but also here as fallback)
    if (event.key === 'Escape' && isOpen.value) {
      close();
    }
  };

  // Set up event listener on mount, clean up on unmount
  onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
  });

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
  });

  const context: CommandPaletteContext = {
    isOpen,
    query,
    recentItems,
    open,
    close,
    toggle,
    addRecentItem,
    clearRecentItems,
  };

  // Provide the context
  provide(COMMAND_PALETTE_INJECTION_KEY, context);

  return {
    isOpen: readonly(isOpen) as Ref<boolean>,
    query,
    recentItems: readonly(recentItems) as Ref<RecentItem[]>,
    open,
    close,
    toggle,
    addRecentItem,
    clearRecentItems,
  };
}

/**
 * Use the command palette from any component
 * Falls back gracefully if no provider exists
 */
export function useCommandPalette(): CommandPaletteContext {
  const context = inject(COMMAND_PALETTE_INJECTION_KEY, null);

  if (!context) {
    // Return a no-op implementation for graceful fallback
    if (import.meta.env.DEV) {
      console.warn('useCommandPalette: No provider found. Make sure AdminLayout uses createCommandPaletteProvider.');
    }

    const noop = () => {};
    return {
      isOpen: ref(false),
      query: ref(''),
      recentItems: ref([]),
      open: noop,
      close: noop,
      toggle: noop,
      addRecentItem: noop,
      clearRecentItems: noop,
    };
  }

  return context;
}
