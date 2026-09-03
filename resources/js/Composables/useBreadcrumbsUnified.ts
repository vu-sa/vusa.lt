import { type Component, type InjectionKey, ref, readonly, provide, inject, onMounted, onUnmounted, watch, type Ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import Home24Filled from '~icons/fluent/home24-filled';
import { TypeIcon } from '@/Components/icons';

/**
 * Unified breadcrumb item interface for both admin and public
 */
export interface BreadcrumbItem {
  label: string;
  icon?: Component;
  href?: string;
  prefetch?: boolean;
}

/**
 * Breadcrumb context type
 */
type BreadcrumbContext = 'admin' | 'public';

/**
 * Where the trail is drawn.
 *
 * `layout` (the default) is the bar `PublicLayout` renders above the page content, and is right
 * for every page whose content starts at the top of the canvas. `band` means the page draws the
 * trail itself, inside its own title band — the design puts it there on detail pages, and without
 * this the layout would render a second one above the band.
 */
export type BreadcrumbPlacement = 'layout' | 'band';

// Global state for breadcrumbs
const globalBreadcrumbs = ref<BreadcrumbItem[]>([]);
const breadcrumbContext = ref<BreadcrumbContext>('admin');
const globalPlacement = ref<BreadcrumbPlacement>('layout');

/**
 * Injection key for the breadcrumbs context
 */
const breadcrumbsSymbol: InjectionKey<ReturnType<typeof createBreadcrumbState>> = Symbol('breadcrumbs-unified');

/**
 * Create a breadcrumb item
 */
export function createBreadcrumbItem(
  label: string,
  href?: string,
  icon?: Component,
  prefetch: boolean = true,
): BreadcrumbItem {
  return { label, href, icon, prefetch };
}

/**
 * Create a breadcrumb item from a route
 */
export function createRouteBreadcrumb(
  label: string,
  routeName?: string,
  params?: any,
  icon?: Component,
  prefetch: boolean = true,
): BreadcrumbItem {
  return {
    label,
    href: routeName ? route(routeName, params) : undefined,
    icon,
    prefetch,
  };
}

/**
 * Get appropriate home breadcrumb based on context
 */
export function getHomeBreadcrumb(context: BreadcrumbContext = 'admin'): BreadcrumbItem {
  if (context === 'public') {
    const page = usePage();
    const { locale } = page.props.app;
    const subdomain = page.props.tenant?.subdomain || 'www';
    return {
      label: $t('Pradinis'),
      href: route('home', { subdomain, lang: locale }),
      icon: Home24Filled,
      prefetch: true,
    };
  }

  // Admin context
  return {
    label: $t('Pradinis'),
    href: route('dashboard'),
    icon: Home24Filled,
    prefetch: true,
  };
}

/**
 * Create global breadcrumb state
 */
export function createBreadcrumbState(context: BreadcrumbContext = 'admin') {
  breadcrumbContext.value = context;

  /**
   * Set breadcrumbs (replaces all current breadcrumbs).
   *
   * Placement is part of the same write, and defaults back to `layout`: a page that says nothing
   * about it must not inherit `band` from whichever detail page the reader came from.
   */
  function set(items: BreadcrumbItem[], placement: BreadcrumbPlacement = 'layout') {
    globalBreadcrumbs.value = [...items];
    globalPlacement.value = placement;
  }

  /**
   * Add a single breadcrumb item to the end
   */
  function add(item: BreadcrumbItem) {
    globalBreadcrumbs.value.push(item);
  }

  /**
   * Clear all breadcrumbs
   */
  function clear() {
    globalBreadcrumbs.value = [];
    globalPlacement.value = 'layout';
  }

  /**
   * Set breadcrumbs to just the home item
   */
  function home() {
    globalBreadcrumbs.value = [getHomeBreadcrumb(context)];
    globalPlacement.value = 'layout';
  }

  const state = {
    breadcrumbs: readonly(globalBreadcrumbs),
    context: readonly(breadcrumbContext),
    placement: readonly(globalPlacement),
    set,
    add,
    clear,
    home,
    // Helper functions
    createBreadcrumbItem,
    createRouteBreadcrumb,
    getHomeBreadcrumb: () => getHomeBreadcrumb(context),
  };

  provide(breadcrumbsSymbol, state);

  return state;
}

/**
 * Use breadcrumbs - simplified unified API with graceful fallback
 */
export function useBreadcrumbs() {
  const breadcrumbs = inject(breadcrumbsSymbol);

  if (!breadcrumbs) {
    console.warn(
      '🍞 [Breadcrumbs] State not provided. Using fallback mode.\n'
      + 'Make sure to use createBreadcrumbState() in your layout component for full functionality.\n'
      + 'Breadcrumbs will not be displayed but the app will continue to work.',
    );

    // Return a fallback breadcrumb state that does nothing but doesn't break
    return {
      breadcrumbs: readonly(ref([])),
      context: readonly(ref('admin' as BreadcrumbContext)),
      placement: readonly(ref('layout' as BreadcrumbPlacement)),
      set: () => console.warn('🍞 [Breadcrumbs] set() called but no state provider found'),
      add: () => console.warn('🍞 [Breadcrumbs] add() called but no state provider found'),
      clear: () => console.warn('🍞 [Breadcrumbs] clear() called but no state provider found'),
      home: () => console.warn('🍞 [Breadcrumbs] home() called but no state provider found'),
      createBreadcrumbItem,
      createRouteBreadcrumb,
      getHomeBreadcrumb: () => getHomeBreadcrumb('admin'),
      // Flag to indicate this is fallback mode
      __isFallback: true,
    };
  }

  return breadcrumbs;
}

/**
 * Automatic breadcrumb management with lifecycle - works for any component
 * This is the recommended way to set breadcrumbs in pages
 */
export function usePageBreadcrumbs(
  breadcrumbsOrGetter: BreadcrumbItem[] | (() => BreadcrumbItem[]) | Ref<BreadcrumbItem[] | undefined>,
  options: {
    includeHome?: boolean;
    clearOnUnmount?: boolean;
    persistDuringNavigation?: boolean;
    /** `band` when the page renders `<PublicBreadcrumbs variant="inline" />` inside its own title band. */
    placement?: BreadcrumbPlacement;
  } = {},
) {
  const { includeHome = true, clearOnUnmount = false, persistDuringNavigation = true, placement = 'layout' } = options;
  const breadcrumbState = useBreadcrumbs();
  const { set, clear, getHomeBreadcrumb } = breadcrumbState;

  // If we're in fallback mode, just return early - no need to set up watchers
  if ('__isFallback' in breadcrumbState) {
    return { updateBreadcrumbs: () => { } };
  }

  // Update breadcrumbs with current value
  function updateBreadcrumbs() {
    let items: BreadcrumbItem[] | undefined;

    if (typeof breadcrumbsOrGetter === 'function') {
      items = breadcrumbsOrGetter();
    }
    else if ('value' in breadcrumbsOrGetter) {
      items = breadcrumbsOrGetter.value;
    }
    else {
      items = breadcrumbsOrGetter;
    }

    if (items && items.length > 0) {
      // Check if home is already included
      const hasHome = items.some(item =>
        item.href === getHomeBreadcrumb().href
        || item.label === $t('Pradinis'),
      );

      // Prepend home if needed and not already present
      const finalItems = (includeHome && !hasHome)
        ? [getHomeBreadcrumb(), ...items]
        : items;

      set(finalItems, placement);
    }
  }

  // Watch for changes if it's a ref
  if ('value' in breadcrumbsOrGetter) {
    watch(() => breadcrumbsOrGetter.value, updateBreadcrumbs, { immediate: true });
  }

  // Set breadcrumbs on mount
  onMounted(updateBreadcrumbs);

  // Only clear on unmount if explicitly requested and not persisting during navigation
  if (clearOnUnmount && !persistDuringNavigation) {
    onUnmounted(clear);
  }

  return { updateBreadcrumbs };
}

/**
 * Breadcrumb helpers - All-in-one helper object for common breadcrumb patterns
 * This is the recommended way to create breadcrumbs
 */
export const BreadcrumbHelpers = {
  /**
   * Create a basic breadcrumb item
   */
  createBreadcrumbItem,

  /**
   * Create a breadcrumb item from a route
   */
  createRouteBreadcrumb,

  /**
   * Get the home breadcrumb item
   */
  homeItem: () => getHomeBreadcrumb('admin'),

  /**
   * Generate admin form breadcrumbs (Create/Edit pages)
   * @example BreadcrumbHelpers.adminForm('Naujienos', 'news.index', 'Nauja naujiena', Icons.NEWS)
   */
  adminForm(sectionName: string, indexRoute: string, currentTitle: string, icon?: Component): BreadcrumbItem[] {
    return [
      getHomeBreadcrumb('admin'),
      createBreadcrumbItem('Administravimas', route('administration'), TypeIcon),
      createRouteBreadcrumb(sectionName, indexRoute, undefined, icon),
      createBreadcrumbItem(currentTitle, undefined, icon),
    ];
  },

  /**
   * Generate admin index breadcrumbs
   * @example BreadcrumbHelpers.adminIndex('Naujienos', Icons.NEWS)
   */
  adminIndex(sectionName: string, icon?: Component): BreadcrumbItem[] {
    return [
      getHomeBreadcrumb('admin'),
      createBreadcrumbItem('Administravimas', route('administration'), TypeIcon),
      createBreadcrumbItem(sectionName, undefined, icon),
    ];
  },

  /**
   * Generate public content breadcrumbs
   * @example BreadcrumbHelpers.publicContent([{label: 'About', href: '/about'}])
   */
  publicContent(items: BreadcrumbItem[]): BreadcrumbItem[] {
    return [
      getHomeBreadcrumb('public'),
      ...items,
    ];
  },

  /**
   * Build breadcrumb path from navigation tree (for public pages)
   * @param navigationItemId The navigation item ID to build path from
   * @param mainNavigation The main navigation structure from page props
   */
  buildNavigationPath(navigationItemId: number | null, mainNavigation: any[]): BreadcrumbItem[] {
    if (!navigationItemId) return [];

    const breadcrumbItems: BreadcrumbItem[] = [];
    let currentId = navigationItemId;

    // Build the breadcrumb path by traversing the navigation tree upwards
    while (currentId) {
      // Find the navigation item in the flattened navigation tree
      const navigationItem = this.findNavigationItem(mainNavigation, currentId);

      if (!navigationItem) break;

      // Add to the beginning of array (reverse order)
      breadcrumbItems.unshift({
        label: navigationItem.name,
        href: navigationItem.url || undefined,
        icon: undefined,
      });

      // Move up to parent
      currentId = navigationItem.parent_id;
    }

    return breadcrumbItems;
  },

  /**
   * Helper to find navigation item by ID (for public pages)
   * @private
   */
  findNavigationItem(navigation: any[], id: number): any {
    // First check root level items
    for (const item of navigation) {
      if (item.id === id) return item;

      // Then check nested items in columns
      if (item.links && Array.isArray(item.links)) {
        // Navigation has columns
        for (const column of item.links) {
          if (Array.isArray(column)) {
            // Search within each column
            for (const link of column) {
              if (link.id === id) return link;
            }
          }
        }
      }
    }

    return null;
  },

  // eslint-disable-next-line no-secrets/no-secrets
  /**
   * Generate entity show breadcrumbs (Admin)
   * @example BreadcrumbHelpers.adminShow('Naujienos', 'news.index', {}, 'Mano naujiena', Icons.NEWS, Icons.NEWS)
   */
  adminShow(
    parentName: string,
    parentRoute: string,
    parentParams: any,
    currentName: string,
    parentIcon?: Component,
    currentIcon?: Component,
  ): BreadcrumbItem[] {
    return [
      getHomeBreadcrumb('admin'),
      createBreadcrumbItem('Administravimas', route('administration'), TypeIcon),
      createRouteBreadcrumb(parentName, parentRoute, parentParams, parentIcon),
      createBreadcrumbItem(currentName, undefined, currentIcon),
    ];
  },
};
