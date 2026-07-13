import type { PostHog } from 'posthog-js';

/**
 * Consent-gated PostHog loader.
 *
 * PostHog must never load or capture before the visitor has opted in to analytics
 * cookies (GDPR/ePrivacy). The SDK is therefore lazy-imported the first time
 * {@link enablePosthog} is called, keeps PostHog's privacy-light memory persistence,
 * and is initialized opted-out by default before we explicitly opt in.
 *
 * Consent is driven by `useCookieConsent`.
 */
let posthogInstance: PostHog | null = null;

/**
 * Load (if needed) and enable PostHog capturing. Production-only and idempotent.
 */
export async function enablePosthog(): Promise<void> {
  if (!import.meta.env.PROD) {
    return;
  }

  if (posthogInstance) {
    posthogInstance.opt_in_capturing();

    return;
  }

  const { default: posthog } = await import('posthog-js');

  posthog.init(import.meta.env.VITE_POSTHOG_API_KEY, {
    api_host: 'https://eu.posthog.com',
    autocapture: {
      dom_event_allowlist: ['click'],
      element_allowlist: ['a', 'button'],
    },
    persistence: 'memory',
    // Nothing is sent until we explicitly opt in below, so a failed/late consent
    // signal can never leak events.
    opt_out_capturing_by_default: true,
  });

  posthog.opt_in_capturing();
  posthogInstance = posthog;

  // Existing custom-event call sites read `window.posthog` directly.
  (window as unknown as { posthog: PostHog }).posthog = posthog;
}

/**
 * Stop capturing and clear any in-memory identifiers. Safe to call when PostHog was
 * never loaded (no-op).
 */
export function disablePosthog(): void {
  if (posthogInstance) {
    posthogInstance.opt_out_capturing();
    posthogInstance.reset();
  }
}
