/**
 * Custom-event helper for the self-hosted Umami tracker.
 *
 * The tracker itself is a `<script>` tag rendered by `app.blade.php` on public pages only,
 * so `window.umami` is legitimately absent on admin pages, on staging (no website id
 * configured) and whenever a blocker intervenes. Every call is therefore a safe no-op.
 *
 * Pageviews — including Inertia navigations — are tracked automatically by the script;
 * only genuinely custom interactions belong here.
 */

interface UmamiTracker {
  track: (name: string, data?: Record<string, unknown>) => void;
}

export function trackEvent(name: string, data?: Record<string, unknown>): void {
  if (typeof window === 'undefined') {
    return;
  }

  (window as unknown as { umami?: UmamiTracker }).umami?.track(name, data);
}
