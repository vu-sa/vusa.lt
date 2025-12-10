/**
 * useFeatureSpotlight - A lightweight composable for highlighting new features
 * 
 * Provides a simple way to draw attention to new UI elements without a full tour:
 * - Pulsing indicator/badge on the element
 * - Tooltip on hover/click with description
 * - Persisted dismissed state (localStorage + backend)
 * 
 * Uses the same storage mechanism as useProductTour for consistency.
 * 
 * @example
 * ```vue
 * <script setup>
 * const spotlight = useFeatureSpotlight('tenant-tab-v1');
 * </script>
 * 
 * <template>
 *   <div class="relative">
 *     <TabsTrigger ref="spotlight.targetRef">Tenant</TabsTrigger>
 *     <SpotlightBadge v-if="spotlight.isVisible.value" @dismiss="spotlight.dismiss" />
 *   </div>
 * </template>
 * ```
 */

import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
  globalProgress, 
  updateProgress, 
  removeProgress 
} from './useTutorialProgress';

// Prefix for spotlight IDs to distinguish from tours
const SPOTLIGHT_PREFIX = 'spotlight-';

export interface FeatureSpotlightOptions {
  /**
   * Title shown in the spotlight popover
   */
  title?: string;
  
  /**
   * Description shown in the spotlight popover
   */
  description?: string;
  
  /**
   * Whether to show the spotlight only for specific user conditions
   * @default true (always show if not dismissed)
   */
  enabled?: boolean;
  
  /**
   * Position of the popover relative to the target element
   * @default 'bottom'
   */
  position?: 'top' | 'bottom' | 'left' | 'right';
  
  /**
   * Callback when spotlight is dismissed
   */
  onDismiss?: () => void;
}

export function useFeatureSpotlight(spotlightId: string, options: FeatureSpotlightOptions = {}) {
  const { title = '', description = '', enabled = true, position = 'bottom', onDismiss } = options;
  
  // Internal ID with prefix
  const internalId = `${SPOTLIGHT_PREFIX}${spotlightId}`;
  
  // State
  const isPopoverOpen = ref(false);
  const targetRef = ref<HTMLElement | null>(null);
  
  // Check if spotlight has been dismissed
  const isDismissed = computed(() => {
    return !!globalProgress.value[internalId];
  });
  
  // Should the spotlight be visible?
  const isVisible = computed(() => {
    return enabled && !isDismissed.value;
  });
  
  /**
   * Dismiss the spotlight (mark as seen)
   */
  async function dismiss(): Promise<void> {
    if (isDismissed.value) return;
    
    // Update shared state
    updateProgress(internalId, new Date().toISOString());
    
    // Close popover
    isPopoverOpen.value = false;
    
    // Callback
    onDismiss?.();
    
    // Sync to backend (async: true hides progress indicator)
    try {
      await router.post(route('tutorials.complete'), {
        tour_id: internalId,
      }, {
        async: true,
        preserveState: true,
        preserveScroll: true,
      });
    } catch (error) {
      console.warn('Failed to sync spotlight dismissal to server:', error);
    }
  }
  
  /**
   * Reset the spotlight (allow it to show again)
   */
  async function reset(): Promise<void> {
    // Update shared state
    removeProgress(internalId);
    
    // Sync to backend (async: true hides progress indicator)
    try {
      await router.post(route('tutorials.reset'), {
        tour_id: internalId,
      }, {
        async: true,
        preserveState: true,
        preserveScroll: true,
      });
    } catch (error) {
      console.warn('Failed to sync spotlight reset to server:', error);
    }
  }
  
  /**
   * Show the popover
   */
  function showPopover(): void {
    if (isVisible.value) {
      isPopoverOpen.value = true;
    }
  }
  
  /**
   * Hide the popover without dismissing
   */
  function hidePopover(): void {
    isPopoverOpen.value = false;
  }
  
  return {
    // State
    isVisible,
    isDismissed,
    isPopoverOpen,
    targetRef,
    
    // Configuration
    title,
    description,
    position,
    
    // Actions
    dismiss,
    reset,
    showPopover,
    hidePopover,
  };
}

// Re-export the shared init function for convenience
export { initProgress as syncSpotlightProgress } from './useTutorialProgress';
