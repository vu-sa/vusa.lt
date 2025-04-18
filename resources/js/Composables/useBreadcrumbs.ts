import { ref, readonly, provide, inject, computed, onMounted, onUnmounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import type { Component, InjectionKey, Ref } from 'vue'
import Home24Filled from '~icons/fluent/home24-filled'
import { trans as $t } from 'laravel-vue-i18n'

/**
 * Breadcrumb item interface
 */
export interface BreadcrumbItem {
  label: string
  icon?: Component
  href?: string
}

// Create a global state to persist across Inertia page transitions
const globalBreadcrumbs = ref<BreadcrumbItem[]>([])

// We'll track the source of current breadcrumbs to manage priority properly
const breadcrumbSource = ref<'component' | 'default' | 'none'>('none')

/**
 * Injection key for the breadcrumbs context
 */
const breadcrumbsSymbol: InjectionKey<ReturnType<typeof createBreadcrumbState>> = Symbol('breadcrumbs')

/**
 * Create a breadcrumb item
 */
function createBreadcrumbItem(
  label: string,
  href?: string,
  icon?: Component
): BreadcrumbItem {
  return {
    label,
    href,
    icon
  }
}

/**
 * Create a breadcrumb item from a route
 */
function createRouteBreadcrumb(
  label: string,
  routeName?: string,
  params?: any,
  icon?: Component
): BreadcrumbItem {
  return {
    label,
    href: routeName ? route(routeName, params) : undefined,
    icon
  }
}

/**
 * Home breadcrumb item - returns a fresh instance with translated label when called
 */
export function homeItem(): BreadcrumbItem {
  return {
    label: $t('Pradinis'),
    href: route('dashboard'),
    icon: Home24Filled
  }
}

/**
 * Create global breadcrumb state for the application
 */
export function createBreadcrumbState() {  
  /**
   * Set breadcrumbs with a specified source
   */
  function setBreadcrumbs(items: BreadcrumbItem[], source: 'component' | 'default' = 'component') {
    // Only update if the source has higher or equal priority
    if (source === 'component' || breadcrumbSource.value === 'none' || breadcrumbSource.value === 'default') {
      globalBreadcrumbs.value = items
      breadcrumbSource.value = source
    }
  }
  
  /**
   * Add a single breadcrumb item
   */
  function addBreadcrumb(item: BreadcrumbItem) {
    globalBreadcrumbs.value.push(item)
    breadcrumbSource.value = 'component'
  }
  
  /**
   * Clear all breadcrumbs
   */
  function clearBreadcrumbs() {
    globalBreadcrumbs.value = []
    breadcrumbSource.value = 'none'
  }
  
  /**
   * Reset breadcrumbs to just the home item
   */
  function resetToHome() {
    globalBreadcrumbs.value = [homeItem()]
    breadcrumbSource.value = 'default'
  }
  
  // Calculate if explicit breadcrumbs have been set by components
  const hasBreadcrumbs = computed(() => 
    breadcrumbSource.value === 'component' && globalBreadcrumbs.value.length > 0
  )
  
  const state = {
    breadcrumbs: readonly(globalBreadcrumbs),
    setBreadcrumbs,
    addBreadcrumb,
    clearBreadcrumbs,
    resetToHome,
    createBreadcrumbItem,
    createRouteBreadcrumb,
    homeItem,
    hasBreadcrumbs,
    breadcrumbSource: readonly(breadcrumbSource)
  }
  
  provide(breadcrumbsSymbol, state)
  
  return state
}

/**
 * Use breadcrumbs throughout the application
 */
export function useBreadcrumbs() {
  const breadcrumbs = inject(breadcrumbsSymbol)
  
  if (!breadcrumbs) {
    throw new Error('Breadcrumbs state not provided. Make sure to use createBreadcrumbState in your parent component')
  }
  
  return breadcrumbs
}

/**
 * Use and manage breadcrumbs with automatic setup and cleanup
 * This composable automatically handles the lifecycle of breadcrumbs
 * and reduces code duplication across components
 */
export function useComponentBreadcrumbs(breadcrumbsOrGetter: BreadcrumbItem[] | (() => BreadcrumbItem[]) | Ref<BreadcrumbItem[] | undefined>) {
  const { setBreadcrumbs } = useBreadcrumbs()
  
  // Update breadcrumbs with current value
  function updateBreadcrumbs() {
    let items: BreadcrumbItem[] | undefined
    
    if (typeof breadcrumbsOrGetter === 'function') {
      items = breadcrumbsOrGetter()
    } else if ('value' in breadcrumbsOrGetter) {
      items = breadcrumbsOrGetter.value
    } else {
      items = breadcrumbsOrGetter
    }
    
    if (items && items.length > 0) {
      // Set explicitly as component breadcrumbs to ensure priority
      setBreadcrumbs(items, 'component')
    }
  }
  
  // Set breadcrumbs when the component mounts and whenever breadcrumbs change
  if ('value' in breadcrumbsOrGetter) {
    watch(() => breadcrumbsOrGetter.value, () => {
      updateBreadcrumbs()
    }, { immediate: true })
  }
  
  // Set breadcrumbs on mount
  onMounted(updateBreadcrumbs)
  
  return {
    updateBreadcrumbs
  }
}