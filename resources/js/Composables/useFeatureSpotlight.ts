/**
 * Pairs with SpotlightPopover and persists dismissal through tutorial progress.
 */

import { ref, computed } from 'vue';

import { useApiMutation } from './useApi';
import {
  globalProgress,
  updateProgress,
  removeProgress,
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

  /** Preferred side of the popover, matching SpotlightPopover's `side` prop. */
  side?: 'top' | 'bottom' | 'left' | 'right';

  /**
   * Callback when spotlight is dismissed
   */
  onDismiss?: () => void;
}

export function useFeatureSpotlight(spotlightId: string, options: FeatureSpotlightOptions = {}) {
  const { title = '', description = '', enabled = true, side = 'bottom', onDismiss } = options;

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

    // Sync to backend using useApiMutation (handles auth automatically)
    try {
      const { execute } = useApiMutation(
        route('api.v1.admin.tutorials.complete'),
        'POST',
        { tour_id: internalId },
        { showSuccessToast: false, showErrorToast: false },
      );
      await execute();
    }
    catch (error) {
      console.warn('Failed to sync spotlight dismissal to server:', error);
    }
  }

  /**
   * Reset the spotlight (allow it to show again)
   */
  async function reset(): Promise<void> {
    // Update shared state
    removeProgress(internalId);

    // Sync to backend using useApiMutation (handles auth automatically)
    try {
      const { execute } = useApiMutation(
        route('api.v1.admin.tutorials.reset'),
        'POST',
        { tour_id: internalId },
        { showSuccessToast: false, showErrorToast: false },
      );
      await execute();
    }
    catch (error) {
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
    side,

    // Actions
    dismiss,
    reset,
    showPopover,
    hidePopover,
  };
}

// Re-export the shared init function for convenience
export { initProgress as syncSpotlightProgress } from './useTutorialProgress';
