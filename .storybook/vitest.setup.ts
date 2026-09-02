import { beforeAll } from 'vitest';

/**
 * The storybook project runs in a real Chromium via @vitest/browser, not jsdom — so the browser
 * APIs a jsdom setup has to polyfill are already there and correct.
 *
 * This file used to install an unconditional `matchMedia` stub that answered `matches: false` to
 * every query. In a real browser that is strictly worse than doing nothing: it made
 * `prefers-color-scheme: dark` and every `useBreakpoints`/responsive query report false, so a
 * story could never exercise its dark or mobile branch. Both stubs are now fallbacks only, for
 * the case where an environment genuinely lacks them.
 */
beforeAll(() => {
  if (typeof window !== 'undefined' && typeof window.matchMedia !== 'function') {
    Object.defineProperty(window, 'matchMedia', {
      writable: true,
      value: (query: string) => ({
        matches: false,
        media: query,
        onchange: null,
        addListener: () => { },
        removeListener: () => { },
        addEventListener: () => { },
        removeEventListener: () => { },
        dispatchEvent: () => false,
      }),
    });
  }

  if (typeof globalThis.ResizeObserver === 'undefined') {
    globalThis.ResizeObserver = class ResizeObserver {
      observe() { }
      unobserve() { }
      disconnect() { }
    } as unknown as typeof ResizeObserver;
  }
});
