/**
 * Shared tutorial progress state module
 *
 * This module provides a single source of truth for tutorial/spotlight progress
 * that is shared between useProductTour and useFeatureSpotlight composables.
 *
 * Progress is loaded from server via Inertia page props (auth.user.tutorial_progress).
 * No localStorage is used to avoid issues when switching users in the same browser.
 */

import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

import type { PageProps } from '@/Types/index';

// Global reactive state - single instance shared across all composables
export const globalProgress = ref<Record<string, string>>({});

// Track if we've initialized from server AND with which user
let hasInitialized = false;
let initializedUserId: number | null = null;

/**
 * Initialize progress from server (Inertia page props)
 * Should be called once when app loads, and re-syncs when user changes
 */
export function initProgress(): void {
  try {
    const page = usePage<PageProps>();

    // Only mark as initialized if we can actually access page props
    // This prevents marking as initialized when called before Inertia is ready
    if (!page.props) {
      return; // Don't set hasInitialized - props not available yet
    }

    const currentUserId = page.props?.auth?.user?.id ?? null;
    const serverProgress = page.props?.auth?.user?.tutorial_progress;

    // Skip if already initialized for this same user
    if (hasInitialized && initializedUserId === currentUserId) {
      return;
    }

    // User changed (login/logout) or first initialization
    if (serverProgress && typeof serverProgress === 'object') {
      globalProgress.value = { ...serverProgress };
    }
    else {
      // User logged out or has no progress - clear it
      globalProgress.value = {};
    }

    // Mark initialized with current user
    hasInitialized = true;
    initializedUserId = currentUserId;
  }
  catch {
    // Page props not available - don't mark as initialized
    // This allows retry on next call
  }
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
  initializedUserId = null;
  globalProgress.value = {};
}

/**
 * Check if progress has been initialized for the current user
 * Returns false if user changed since last initialization (e.g., after login)
 */
export function isInitialized(): boolean {
  if (!hasInitialized) return false;

  try {
    const page = usePage<PageProps>();
    const currentUserId = page.props?.auth?.user?.id ?? null;

    // If user changed, we need to re-initialize
    return initializedUserId === currentUserId;
  }
  catch {
    return hasInitialized;
  }
}
