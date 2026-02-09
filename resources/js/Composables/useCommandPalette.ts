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

import { ref, provide, inject, readonly, onMounted, onUnmounted, type InjectionKey, type Ref } from 'vue';
import { useEventListener, useLocalStorage } from '@vueuse/core';

// Types
export interface RecentItem {
  id: string;
  type: 'meeting' | 'agenda_item' | 'action' | 'news' | 'page' | 'calendar' | 'institution' | 'document';
  title: string;
  href?: string;
  timestamp: number;
}

interface CommandPaletteContext {
  /** Whether the command palette is currently open */
  isOpen: Ref<boolean>;
  /** Current search query */
  query: Ref<string>;
  /** Recent items (persisted to localStorage) */
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

const MAX_RECENT_ITEMS = 5;
const RECENT_ITEMS_KEY = 'vusa-command-palette-recent';

/**
 * Creates the command palette provider context (call in AdminLayout)
 */
export function createCommandPaletteProvider(): CommandPaletteContext {
  const isOpen = ref(false);
  const query = ref('');
  const recentItems = useLocalStorage<RecentItem[]>(RECENT_ITEMS_KEY, []);

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

  const addRecentItem = (item: Omit<RecentItem, 'timestamp'>) => {
    // Remove duplicate if exists
    recentItems.value = recentItems.value.filter(
      r => !(r.id === item.id && r.type === item.type),
    );

    // Add to beginning
    recentItems.value.unshift({
      ...item,
      timestamp: Date.now(),
    });

    // Keep only MAX_RECENT_ITEMS
    if (recentItems.value.length > MAX_RECENT_ITEMS) {
      recentItems.value = recentItems.value.slice(0, MAX_RECENT_ITEMS);
    }
  };

  const clearRecentItems = () => {
    recentItems.value = [];
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
