import { nextTick } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

const enablePosthog = vi.fn();
const disablePosthog = vi.fn();

vi.mock('@/Plugins/posthog', () => ({
  enablePosthog: (...args: unknown[]) => enablePosthog(...args),
  disablePosthog: (...args: unknown[]) => disablePosthog(...args),
}));

// The composable holds a module-level singleton, so we reset modules per test to get a
// clean state seeded from localStorage.
async function freshConsent() {
  vi.resetModules();
  const mod = await import('@/Composables/useCookieConsent');

  return mod.useCookieConsent();
}

describe('useCookieConsent', () => {
  beforeEach(() => {
    localStorage.clear();
    enablePosthog.mockClear();
    disablePosthog.mockClear();
  });

  it('starts undecided with analytics off (no tracking before a choice)', async () => {
    const c = await freshConsent();

    expect(c.decided.value).toBe(false);
    expect(c.analyticsAllowed.value).toBe(false);
    expect(enablePosthog).not.toHaveBeenCalled();
  });

  it('acceptAll records the decision and enables analytics', async () => {
    const c = await freshConsent();

    c.acceptAll();

    expect(c.decided.value).toBe(true);
    expect(c.analyticsAllowed.value).toBe(true);
    expect(enablePosthog).toHaveBeenCalledTimes(1);
    expect(disablePosthog).not.toHaveBeenCalled();
  });

  it('rejectAll records the decision and disables analytics', async () => {
    const c = await freshConsent();

    c.rejectAll();

    expect(c.decided.value).toBe(true);
    expect(c.analyticsAllowed.value).toBe(false);
    expect(disablePosthog).toHaveBeenCalledTimes(1);
    expect(enablePosthog).not.toHaveBeenCalled();
  });

  it('setAnalytics toggles tracking on and off', async () => {
    const c = await freshConsent();

    c.setAnalytics(true);
    expect(c.analyticsAllowed.value).toBe(true);
    expect(enablePosthog).toHaveBeenCalledTimes(1);

    c.setAnalytics(false);
    expect(c.analyticsAllowed.value).toBe(false);
    expect(disablePosthog).toHaveBeenCalledTimes(1);
  });

  it('reopen clears the decision (re-shows the banner) but keeps the choice', async () => {
    const c = await freshConsent();

    c.acceptAll();
    c.reopen();

    expect(c.decided.value).toBe(false);
    expect(c.analyticsAllowed.value).toBe(true);
  });

  it('persists the choice to localStorage', async () => {
    const c = await freshConsent();

    c.acceptAll();
    await nextTick();

    expect(JSON.parse(localStorage.getItem('cookie-consent-v2') ?? '{}')).toEqual({
      decided: true,
      analytics: true,
    });
  });

  it('initAnalyticsFromConsent enables analytics only when previously opted in', async () => {
    localStorage.setItem('cookie-consent-v2', JSON.stringify({ decided: true, analytics: true }));
    const c = await freshConsent();

    c.initAnalyticsFromConsent();

    expect(enablePosthog).toHaveBeenCalledTimes(1);
  });

  it('initAnalyticsFromConsent does nothing when the visitor declined', async () => {
    localStorage.setItem('cookie-consent-v2', JSON.stringify({ decided: true, analytics: false }));
    const c = await freshConsent();

    c.initAnalyticsFromConsent();

    expect(enablePosthog).not.toHaveBeenCalled();
  });
});
