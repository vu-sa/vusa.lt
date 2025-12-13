/**
 * useProductTour - A composable for managing product tours with Driver.js
 * 
 * Provides a Vue-friendly wrapper around Driver.js with:
 * - Backend synchronization of tour completion
 * - localStorage fallback for offline scenarios
 * - Reactive state for tour progress
 * - Support for versioned tours (increment version to show tour again)
 * 
 * @example
 * ```ts
 * const { startTour, hasCompleted, isActive } = useProductTour('atstovavimas-overview-v1', {
 *   steps: [
 *     { element: '[data-tour="institution-card"]', popover: { title: 'Title', description: 'Desc' } }
 *   ]
 * });
 * 
 * onMounted(() => {
 *   if (!hasCompleted.value) {
 *     startTour();
 *   }
 * });
 * ```
 */

import { ref, computed, onUnmounted } from 'vue';
import { driver, type Driver, type DriveStep, type Config } from 'driver.js';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import 'driver.js/dist/driver.css';
import { 
  globalProgress, 
  updateProgress, 
  removeProgress 
} from './useTutorialProgress';

export interface ProductTourOptions {
  /**
   * Unique identifier for this tour (e.g., 'atstovavimas-overview-v1')
   */
  tourId: string;
  
  /**
   * Tour steps configuration for Driver.js.
   * Can be an array of steps or a function that returns steps.
   * Using a function allows lazy evaluation of translations.
   */
  steps: DriveStep[] | (() => DriveStep[]);
  
  /**
   * Additional Driver.js configuration options
   */
  driverConfig?: Partial<Config>;
  
  /**
   * Whether to auto-start the tour when using startTourIfNew
   * @default true
   */
  autoStart?: boolean;
  
  /**
   * Callback when tour is completed
   */
  onComplete?: () => void;
}

export function useProductTour(options: ProductTourOptions) {
  const { tourId, steps: stepsOrFn, driverConfig = {}, onComplete } = options;
  
  // Resolve steps lazily - allows translations to load before evaluation
  const resolveSteps = (): DriveStep[] => {
    return typeof stepsOrFn === 'function' ? stepsOrFn() : stepsOrFn;
  };
  
  const isActive = ref(false);
  const currentStep = ref(0);
  const isVoluntaryTour = ref(false);
  let driverInstance: Driver | null = null;
  
  // Check if tour has been completed
  const hasCompleted = computed(() => {
    return !!globalProgress.value[tourId];
  });
  
  // Get completion timestamp
  const completedAt = computed(() => {
    return globalProgress.value[tourId] || null;
  });
  
  /**
   * Mark the tour as completed both locally and on the server
   */
  async function markCompleted(): Promise<void> {
    // Update shared state and localStorage
    updateProgress(tourId, new Date().toISOString());
    
    // Sync to backend (async: true hides progress indicator)
    try {
      await router.post(route('tutorials.complete'), {
        tour_id: tourId,
      }, {
        async: true,
        preserveState: true,
        preserveScroll: true,
      });
    } catch (error) {
      console.warn('Failed to sync tour completion to server:', error);
    }
  }
  
  /**
   * Reset the tour (allow replay)
   */
  async function resetTour(): Promise<void> {
    // Update shared state and localStorage
    removeProgress(tourId);
    
    // Sync to backend (async: true hides progress indicator)
    try {
      await router.post(route('tutorials.reset'), {
        tour_id: tourId,
      }, {
        async: true,
        preserveState: true,
        preserveScroll: true,
      });
    } catch (error) {
      console.warn('Failed to sync tour reset to server:', error);
    }
  }
  
  /**
   * Start the product tour
   * @param isVoluntary - If true, allows user to close/skip at any time without penalty
   */
  function startTour(isVoluntary = false): void {
    // Resolve steps at tour start time (lazy evaluation for translations)
    const steps = resolveSteps();
    
    if (isActive.value || steps.length === 0) return;
    
    isVoluntaryTour.value = isVoluntary;
    
    // Create driver instance with merged config
    driverInstance = driver({
      // Step configuration
      steps: steps.map((step) => ({
        ...step,
        // Disable interaction with highlighted elements
        disableActiveInteraction: true,
        popover: {
          ...step.popover,
          // Add welcome class for first step if no element
          popoverClass: !step.element 
            ? 'vusa-tour-popover vusa-tour-popover-welcome' 
            : 'vusa-tour-popover',
        },
      })),
      
      // Animation and scrolling
      animate: true,
      smoothScroll: true,
      
      // Overlay styling
      overlayColor: 'black',
      overlayOpacity: 0.75,
      
      // Highlighted element styling
      stagePadding: 12,
      stageRadius: 8,
      
      // Popover positioning and styling
      popoverOffset: 16,
      popoverClass: 'vusa-tour-popover',
      
      // Navigation behavior
      allowKeyboardControl: true,
      allowClose: isVoluntary,
      // When clicking overlay: do nothing for involuntary, close for voluntary
      overlayClickBehavior: isVoluntary ? 'close' : undefined,
      // Prevent clicking on highlighted elements
      disableActiveInteraction: true,
      
      // Progress display
      showProgress: true,
      // Use {{current}} and {{total}} placeholders as per Driver.js docs
      progressText: $t('tutorials.step_of').replace('{{current}}', '{{current}}').replace('{{total}}', '{{total}}'),
      
      // Button configuration
      showButtons: isVoluntary ? ['next', 'previous', 'close'] : ['next', 'previous'],
      nextBtnText: $t('tutorials.next'),
      prevBtnText: $t('tutorials.previous'),
      doneBtnText: $t('tutorials.done'),
      
      // Override with user config (before lifecycle hooks)
      ...driverConfig,
      
      // Lifecycle hooks (always applied, can't be overridden)
      onHighlightStarted: (element, step, opts) => {
        currentStep.value = opts.state.activeIndex ?? 0;
        driverConfig?.onHighlightStarted?.(element, step, opts);
      },
      
      onDestroyed: (element, step, opts) => {
        const wasLastStep = currentStep.value === steps.length - 1;
        isActive.value = false;
        
        // For voluntary tours: always mark complete when closed (user chose to open it)
        // For involuntary tours: only mark complete if user finished the last step
        if (isVoluntaryTour.value || wasLastStep) {
          markCompleted();
          onComplete?.();
        }
        
        isVoluntaryTour.value = false;
        driverInstance = null;
        driverConfig?.onDestroyed?.(element, step, opts);
      },
    });
    
    isActive.value = true;
    driverInstance.drive();
  }
  
  /**
   * Start tour only if user hasn't completed it yet
   */
  function startTourIfNew(): void {
    if (!hasCompleted.value) {
      // Small delay to ensure DOM is ready
      requestAnimationFrame(() => {
        startTour();
      });
    }
  }
  
  /**
   * Move to specific step
   */
  function goToStep(index: number): void {
    driverInstance?.moveTo(index);
  }
  
  /**
   * Clean up on unmount
   */
  onUnmounted(() => {
    if (driverInstance) {
      // Force destroy without triggering completion
      isActive.value = false;
      driverInstance.destroy();
      driverInstance = null;
    }
  });
  
  return {
    // State
    isActive,
    currentStep,
    hasCompleted,
    completedAt,
    
    // Actions
    startTour,
    startTourIfNew,
    resetTour,
    goToStep,
  };
}

// Re-export shared functions for backward compatibility
export { initProgress as initTourProgress, globalProgress } from './useTutorialProgress';

/**
 * Get current global tour progress
 */
export function getTourProgress(): Record<string, string> {
  return globalProgress.value;
}
