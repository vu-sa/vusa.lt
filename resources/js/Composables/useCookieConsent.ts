import { computed } from 'vue';
import { useStorage } from '@vueuse/core';

/**
 * Cookie notice state.
 *
 * Analytics is handled by self-hosted Umami, which is cookieless and stores nothing on
 * the visitor's device, so there is nothing to opt in to — the only cookie we set is the
 * session cookie the site needs to function. This composable therefore just remembers
 * whether the visitor has acknowledged that notice.
 */
export interface CookieConsent {
  decided: boolean;
}

/**
 * Storage key is intentionally unchanged from the previous opt-in implementation so that
 * visitors who already dismissed the banner are not shown it again. `mergeDefaults`
 * discards the now-unused `analytics` field on read.
 */
const STORAGE_KEY = 'cookie-consent-v2';

// Module-level singleton so every importer (layout, banner, footer) shares one reactive
// source of truth.
const consent = useStorage<CookieConsent>(
  STORAGE_KEY,
  { decided: false },
  localStorage,
  { mergeDefaults: true },
);

export function useCookieConsent() {
  const decided = computed(() => consent.value.decided);

  /** Dismiss the notice. */
  function acknowledge(): void {
    consent.value = { decided: true };
  }

  /** Re-show the notice (footer link). */
  function reopen(): void {
    consent.value = { decided: false };
  }

  return {
    consent,
    decided,
    acknowledge,
    reopen,
  };
}
