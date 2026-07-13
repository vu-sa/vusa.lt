import { computed } from 'vue';
import { useStorage } from '@vueuse/core';

import { disablePosthog, enablePosthog } from '@/Plugins/posthog';

/**
 * GDPR cookie-consent state.
 *
 * `decided` distinguishes "the visitor has not yet made a choice" (show the banner)
 * from "the visitor chose to decline analytics" (don't show, don't track).
 */
export interface CookieConsent {
  decided: boolean;
  analytics: boolean;
}

/**
 * New storage key. The legacy `'cookie-consent'` boolean only recorded that the
 * necessary-cookie notice was dismissed and never captured an analytics choice, so we
 * intentionally start fresh here to obtain an explicit analytics opt-in.
 */
const STORAGE_KEY = 'cookie-consent-v2';

// Module-level singleton so every importer (entry points, layout, banner) shares one
// reactive source of truth.
const consent = useStorage<CookieConsent>(
  STORAGE_KEY,
  { decided: false, analytics: false },
  localStorage,
  { mergeDefaults: true },
);

/**
 * Apply the current analytics choice to the analytics SDK. Loading/capturing only ever
 * happens here, never before the visitor has opted in.
 */
function applyAnalyticsConsent(): void {
  if (consent.value.analytics) {
    void enablePosthog();
  } else {
    disablePosthog();
  }
}

export function useCookieConsent() {
  const decided = computed(() => consent.value.decided);
  const analyticsAllowed = computed(() => consent.value.analytics);

  function acceptAll(): void {
    consent.value = { decided: true, analytics: true };
    applyAnalyticsConsent();
  }

  function rejectAll(): void {
    consent.value = { decided: true, analytics: false };
    applyAnalyticsConsent();
  }

  function setAnalytics(value: boolean): void {
    consent.value = { decided: true, analytics: value };
    applyAnalyticsConsent();
  }

  /** Re-show the banner so the visitor can change or withdraw their choice. */
  function reopen(): void {
    consent.value = { ...consent.value, decided: false };
  }

  /**
   * Call once on app startup: if the visitor previously opted in, (re)enable analytics.
   * Does nothing when undecided or declined, so nothing loads pre-consent.
   */
  function initAnalyticsFromConsent(): void {
    if (consent.value.decided && consent.value.analytics) {
      void enablePosthog();
    }
  }

  return {
    consent,
    decided,
    analyticsAllowed,
    acceptAll,
    rejectAll,
    setAnalytics,
    reopen,
    initAnalyticsFromConsent,
  };
}
