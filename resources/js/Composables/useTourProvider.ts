/**
 * useTourProvider - Provides a way for pages to register their product tour
 * so that a consistent help button in AdminLayout can trigger it.
 * 
 * @example
 * // In a page component (e.g., ShowAdminHome.vue):
 * const { startTour } = useProductTour({ ... });
 * provideTour(startTour); // Register the tour start function
 * 
 * // In AdminLayout.vue:
 * const { hasTour, startTour } = useTour();
 * // Show help button only if hasTour.value is true
 */

import { ref, provide, inject, type InjectionKey, type Ref, computed, readonly } from 'vue';

interface TourContext {
  /**
   * Function to start the tour (voluntary mode)
   */
  startTour: () => void;
  
  /**
   * Whether a tour is currently registered
   */
  hasTour: Ref<boolean>;
}

const TOUR_INJECTION_KEY: InjectionKey<{
  startTour: Ref<(() => void) | null>;
  setTour: (fn: () => void) => void;
  clearTour: () => void;
}> = Symbol('tour-provider');

/**
 * Creates the tour provider context (call in AdminLayout)
 */
export function createTourProvider() {
  const tourFn = ref<(() => void) | null>(null);
  
  const setTour = (fn: () => void) => {
    tourFn.value = fn;
  };
  
  const clearTour = () => {
    tourFn.value = null;
  };
  
  const hasTour = computed(() => tourFn.value !== null);
  
  const startTour = () => {
    if (tourFn.value) {
      tourFn.value();
    }
  };
  
  // Provide the context
  provide(TOUR_INJECTION_KEY, {
    startTour: tourFn,
    setTour,
    clearTour,
  });
  
  return {
    hasTour: readonly(hasTour),
    startTour,
    clearTour, // Expose clearTour so layout can call it on navigation
  };
}

/**
 * Register a tour from a page component
 * @param startTourFn Function that starts the tour (typically from useProductTour)
 */
export function provideTour(startTourFn: (isVoluntary?: boolean) => void) {
  const context = inject(TOUR_INJECTION_KEY, null);
  
  if (!context) {
    console.warn('provideTour: No tour provider found. Make sure AdminLayout uses createTourProvider.');
    return;
  }
  
  // Wrap to always call with isVoluntary=true when triggered from help button
  context.setTour(() => startTourFn(true));
  
  // Return cleanup function for manual use if needed
  return () => {
    context.clearTour();
  };
}

/**
 * Clears the current tour registration
 */
export function clearTour() {
  const context = inject(TOUR_INJECTION_KEY, null);
  
  if (context) {
    context.clearTour();
  }
}
