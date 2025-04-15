import { ref, readonly, provide, inject, computed } from 'vue'
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

// Track if a component has explicitly set breadcrumbs to prevent default behavior
const breadcrumbsExplicitlySet = ref(false)

const breadcrumbsSymbol: InjectionKey<{
  breadcrumbs: Ref<BreadcrumbItem[]>
  setBreadcrumbs: (breadcrumbs: BreadcrumbItem[]) => void
  addBreadcrumb: (breadcrumb: BreadcrumbItem) => void
  clearBreadcrumbs: () => void
  resetToHome: () => void
  createBreadcrumbItem: (label: string, href?: string, icon?: Component) => BreadcrumbItem
  createRouteBreadcrumb: (label: string, routeName: string, params?: any, icon?: Component) => BreadcrumbItem
  homeItem: () => BreadcrumbItem
  hasBreadcrumbs: Ref<boolean>
}> = Symbol('breadcrumbs')

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
  routeName: string,
  params?: any,
  icon?: Component
): BreadcrumbItem {
  return {
    label,
    href: route(routeName, params),
    icon
  }
}

/**
 * Home breadcrumb item - returns a fresh instance with translated label when called
 */
function homeItem(): BreadcrumbItem {
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
  function setBreadcrumbs(items: BreadcrumbItem[]) {
    globalBreadcrumbs.value = items
    breadcrumbsExplicitlySet.value = items.length > 0
  }
  
  function addBreadcrumb(item: BreadcrumbItem) {
    globalBreadcrumbs.value.push(item)
    breadcrumbsExplicitlySet.value = true
  }
  
  function clearBreadcrumbs() {
    globalBreadcrumbs.value = []
    breadcrumbsExplicitlySet.value = false
  }
  
  function resetToHome() {
    globalBreadcrumbs.value = []
    breadcrumbsExplicitlySet.value = false
  }
  
  // Setup Inertia navigation event listeners to maintain breadcrumb state
  router.on('start', (event) => {
    // Mark breadcrumbs as not explicitly set on navigation start
    // This will allow components to set their own breadcrumbs
    breadcrumbsExplicitlySet.value = false
  })
  
  provide(breadcrumbsSymbol, {
    breadcrumbs: globalBreadcrumbs,
    setBreadcrumbs,
    addBreadcrumb,
    clearBreadcrumbs,
    resetToHome,
    createBreadcrumbItem,
    createRouteBreadcrumb,
    homeItem,
    hasBreadcrumbs: breadcrumbsExplicitlySet
  })
  
  return {
    breadcrumbs: globalBreadcrumbs,
    setBreadcrumbs,
    addBreadcrumb,
    clearBreadcrumbs,
    resetToHome,
    createBreadcrumbItem,
    createRouteBreadcrumb,
    homeItem,
    hasBreadcrumbs: breadcrumbsExplicitlySet
  }
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