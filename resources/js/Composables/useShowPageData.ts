/**
 * Shared composable for show page functionality
 *
 * Provides:
 * - Deferred content rendering (for heavy components)
 * - Tab state persistence
 * - Entity ID tracking for state resets
 */

import { ref, onMounted, watch, type Ref } from 'vue';
import { useStorage } from '@vueuse/core';

interface UseShowPageDataOptions {
  /**
   * Unique key for this show page type (e.g., 'institution', 'meeting')
   * Used for localStorage keys
   */
  tabKey: string;

  /**
   * Default tab to show
   */
  defaultTab?: string;

  /**
   * Current entity ID - used to reset tab when viewing a different entity
   */
  entityId?: string | number;

  /**
   * Delay in ms before marking deferred content as ready
   * Allows view transitions to complete
   */
  deferredDelay?: number;
}

interface UseShowPageDataReturn {
  /** Whether deferred/heavy content should be rendered */
  deferredContentReady: Ref<boolean>;

  /** Current active tab */
  currentTab: Ref<string>;

  /** Navigate to a specific tab */
  navigateToTab: (tab: string) => void;
}

/**
 * Composable for show page data management
 *
 * @example
 * ```ts
 * const { deferredContentReady, currentTab, navigateToTab } = useShowPageData({
 *   tabKey: 'institution',
 *   entityId: props.institution.id,
 *   defaultTab: 'overview'
 * })
 * ```
 */
export function useShowPageData(options: UseShowPageDataOptions): UseShowPageDataReturn {
  const {
    tabKey,
    defaultTab = 'overview',
    entityId,
    deferredDelay = 100,
  } = options;

  // Deferred rendering state - prevents lag during view transitions
  const deferredContentReady = ref(false);

  // Tab state with localStorage persistence
  const storedTab = useStorage(`show-${tabKey}-tab`, defaultTab);
  const currentTab = ref(storedTab.value);

  // Entity tracking for tab resets
  const lastVisitedEntityId = useStorage(`show-${tabKey}-last-id`, '');

  // Initialize deferred content after mount
  onMounted(() => {
    // Wait for view transition to complete before rendering heavy content
    requestAnimationFrame(() => {
      setTimeout(() => {
        deferredContentReady.value = true;
      }, deferredDelay);
    });

    // Reset to default tab when visiting a different entity
    if (entityId && String(lastVisitedEntityId.value) !== String(entityId)) {
      currentTab.value = defaultTab;
      lastVisitedEntityId.value = String(entityId);
    }
  });

  // Sync tab changes to storage
  watch(currentTab, (newTab) => {
    storedTab.value = newTab;
  });

  // Navigation helper
  const navigateToTab = (tab: string) => {
    currentTab.value = tab;
  };

  return {
    deferredContentReady,
    currentTab,
    navigateToTab,
  };
}
