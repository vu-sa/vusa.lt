import { ref, readonly, computed, reactive } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { Component } from 'vue'
import { type BreadcrumbItem } from '@/Composables/useBreadcrumbs'

// Type for breadcrumb sources to track where breadcrumbs come from
type BreadcrumbSource = 'navigation' | 'component' | 'default';

/**
 * Creates a breadcrumb state for public pages
 * This is similar to the admin breadcrumb state but simplified for public pages
 */
export function createPublicBreadcrumbState() {
  const breadcrumbs = ref<BreadcrumbItem[]>([]);
  const source = ref<BreadcrumbSource>('default');
  const hasBreadcrumbs = computed(() => breadcrumbs.value.length > 0);

  const setBreadcrumbs = (items: BreadcrumbItem[], from: BreadcrumbSource = 'component') => {
    breadcrumbs.value = items;
    source.value = from;
  };

  const clearBreadcrumbs = () => {
    breadcrumbs.value = [];
    source.value = 'default';
  };

  return {
    breadcrumbs: readonly(breadcrumbs),
    hasBreadcrumbs,
    source: readonly(source),
    setBreadcrumbs,
    clearBreadcrumbs
  };
}

// Create a global breadcrumb state for public pages
export const publicBreadcrumbState = createPublicBreadcrumbState();

/**
 * Composable for managing breadcrumbs in public pages
 * This is a simpler version compared to the admin breadcrumbs system
 * as public pages typically have simpler structures
 */
export function usePublicBreadcrumbs() {
  const page = usePage()
  
  const buildNavigationPath = (navigationItemId: number | null): BreadcrumbItem[] => {
    if (!navigationItemId) return []
    
    const mainNavigation = page.props.mainNavigation || []
    const breadcrumbItems: BreadcrumbItem[] = []
    let currentId = navigationItemId
    
    // Build the breadcrumb path by traversing the navigation tree upwards
    while (currentId) {
      // Find the navigation item in the flattened navigation tree
      const navigationItem = findNavigationItem(mainNavigation, currentId)
      
      if (!navigationItem) break
      
      // Add to the beginning of array (reverse order)
      breadcrumbItems.unshift({
        label: navigationItem.name,
        href: navigationItem.url || undefined,
        icon: undefined // Add icon support if needed
      })
      
      // Move up to parent
      currentId = navigationItem.parent_id
    }
    
    return breadcrumbItems
  }
  
  const findNavigationItem = (navigation: any[], id: number): any => {
    // First check root level items
    for (const item of navigation) {
      if (item.id === id) return item
      
      // Then check nested items in columns
      if (item.links && Array.isArray(item.links)) {
        // Navigation has columns
        for (const column of item.links) {
          if (Array.isArray(column)) {
            // Search within each column
            for (const link of column) {
              if (link.id === id) return link
            }
          }
        }
      }
    }
    
    return null
  }
  
  /**
   * Create a breadcrumb item for public pages
   */
  const createPublicBreadcrumbItem = (
    label: string,
    href?: string,
    icon?: Component
  ): BreadcrumbItem => {
    return {
      label,
      href,
      icon
    }
  }
  
  /**
   * Create a route-based breadcrumb item for public pages
   */
  const createPublicRouteBreadcrumb = (
    label: string,
    routeName: string,
    params?: any,
    icon?: Component
  ): BreadcrumbItem => {
    return {
      label,
      href: route(routeName, params),
      icon
    }
  }

  /**
   * Set breadcrumbs for the current page
   */
  const setPageBreadcrumbs = (items: BreadcrumbItem[], source: BreadcrumbSource = 'component') => {
    publicBreadcrumbState.setBreadcrumbs(items, source);
  }
  
  return {
    buildNavigationPath,
    createPublicBreadcrumbItem,
    createPublicRouteBreadcrumb,
    setPageBreadcrumbs,
    clearBreadcrumbs: publicBreadcrumbState.clearBreadcrumbs,
    breadcrumbs: publicBreadcrumbState.breadcrumbs,
    hasBreadcrumbs: publicBreadcrumbState.hasBreadcrumbs
  }
}