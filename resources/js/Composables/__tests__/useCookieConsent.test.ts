import { nextTick } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

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
  });

  it('starts undecided so the notice is shown', async () => {
    const c = await freshConsent();

    expect(c.decided.value).toBe(false);
  });

  it('acknowledge records the decision', async () => {
    const c = await freshConsent();

    c.acknowledge();

    expect(c.decided.value).toBe(true);
  });

  it('reopen clears the decision so the notice shows again', async () => {
    const c = await freshConsent();

    c.acknowledge();
    c.reopen();

    expect(c.decided.value).toBe(false);
  });

  it('persists the decision to localStorage', async () => {
    const c = await freshConsent();

    c.acknowledge();
    await nextTick();

    expect(JSON.parse(localStorage.getItem('cookie-consent-v2') ?? '{}')).toEqual({
      decided: true,
    });
  });

  it('keeps the notice dismissed for visitors who decided under the old opt-in banner', async () => {
    localStorage.setItem('cookie-consent-v2', JSON.stringify({ decided: true, analytics: false }));

    const c = await freshConsent();

    expect(c.decided.value).toBe(true);
  });
});
