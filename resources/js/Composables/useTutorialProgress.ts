/**
 * Shared tutorial progress state module
 * 
 * This module provides a single source of truth for tutorial/spotlight progress
 * that is shared between useProductTour and useFeatureSpotlight composables.
 * 
 * Progress is loaded from server via Inertia page props (auth.user.tutorial_progress).
 * No localStorage is used to avoid issues when switching users in the same browser.
 */

import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/Types/index';

// Global reactive state - single instance shared across all composables
export const globalProgress = ref<Record<string, string>>({});

// Track if we've initialized from server
let hasInitialized = false;

/**
 * Initialize progress from server (Inertia page props)
 * Should be called once when app loads
 */
export function initProgress(): void {
  if (hasInitialized) return;
  
  try {
    const page = usePage<PageProps>();
    const serverProgress = page.props?.auth?.user?.tutorial_progress;
    
    if (serverProgress && typeof serverProgress === 'object') {
      globalProgress.value = { ...serverProgress };
    }
  } catch {
    // Page props not available
  }
  
  hasInitialized = true;
}

/**
 * Update a single tutorial's progress (local state only, backend synced separately)
 */
export function updateProgress(tourId: string, timestamp: string): void {
  globalProgress.value = {
    ...globalProgress.value,
    [tourId]: timestamp,
  };
}

/**
 * Remove a tutorial's progress (for reset)
 */
export function removeProgress(tourId: string): void {
  const newProgress = { ...globalProgress.value };
  delete newProgress[tourId];
  globalProgress.value = newProgress;
}

/**
 * Force re-initialization (useful after login/logout)
 */
export function resetInitialization(): void {
  hasInitialized = false;
  globalProgress.value = {};
}
