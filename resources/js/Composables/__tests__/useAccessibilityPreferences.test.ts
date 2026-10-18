import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

const originalMatchMedia = window.matchMedia;
let motionQuery: MediaQueryList;

// Each import owns a fresh copy of the shared preferences.
async function loadFresh() {
  vi.resetModules();

  return import('../useAccessibilityPreferences');
}

beforeEach(() => {
  localStorage.clear();
  document.documentElement.className = '';
  document.documentElement.removeAttribute('style');
  motionQuery = Object.assign(new EventTarget(), {
    matches: false,
    media: '(prefers-reduced-motion: reduce)',
    onchange: null,
    addListener: vi.fn(),
    removeListener: vi.fn(),
  });
  window.matchMedia = vi.fn(() => motionQuery);
});

afterEach(() => {
  vi.restoreAllMocks();
  window.matchMedia = originalMatchMedia;
});

describe('useAccessibilityPreferences', () => {
  it('uses instant scrolling for either reduced-motion source', async () => {
    const { useAccessibilityPreferences, preferredScrollBehavior } = await loadFresh();
    expect(preferredScrollBehavior()).toBe('smooth');
    useAccessibilityPreferences().reduceMotion.value = true;
    expect(preferredScrollBehavior()).toBe('instant');
    useAccessibilityPreferences().reduceMotion.value = false;
    Object.assign(motionQuery, { matches: true });
    expect(preferredScrollBehavior()).toBe('instant');
  });

  it('starts at the defaults and applies a neutral scale', async () => {
    const { useAccessibilityPreferences } = await loadFresh();
    const { fontScale, contrast, underlineLinks, reduceMotion, isDefault } = useAccessibilityPreferences();

    expect(fontScale.value).toBe('m');
    expect(contrast.value).toBe(false);
    expect(underlineLinks.value).toBe(false);
    expect(reduceMotion.value).toBe(false);
    expect(isDefault.value).toBe(true);
    expect(document.documentElement.style.getPropertyValue('--a11y-font-scale')).toBe('1');
    expect(document.documentElement.dataset.a11yFontScale).toBe('m');
    expect(document.documentElement.classList.contains('reduce-motion')).toBe(false);
  });

  it('drives the root element and persists when a preference changes', async () => {
    const { useAccessibilityPreferences, FONT_SCALES } = await loadFresh();
    const { fontScale, contrast, underlineLinks, reduceMotion, isDefault } = useAccessibilityPreferences();

    fontScale.value = 'xl';
    contrast.value = true;
    underlineLinks.value = true;
    reduceMotion.value = true;
    await nextTick();

    expect(document.documentElement.style.getPropertyValue('--a11y-font-scale'))
      .toBe(String(FONT_SCALES.xl));
    expect(document.documentElement.dataset.a11yFontScale).toBe('xl');
    expect(document.documentElement.classList.contains('a11y-contrast')).toBe(true);
    expect(document.documentElement.classList.contains('a11y-underline')).toBe(true);
    expect(document.documentElement.classList.contains('reduce-motion')).toBe(true);
    expect(isDefault.value).toBe(false);

    expect(JSON.parse(localStorage.getItem('vusa-a11y') as string)).toEqual({
      fontScale: 'xl',
      contrast: true,
      underlineLinks: true,
      reduceMotion: true,
    });
  });

  it('restores stored preferences on a later visit', async () => {
    localStorage.setItem('vusa-a11y', JSON.stringify({
      fontScale: 'l', contrast: true, underlineLinks: false, reduceMotion: true,
    }));

    const { useAccessibilityPreferences } = await loadFresh();
    const { fontScale, contrast, underlineLinks, reduceMotion } = useAccessibilityPreferences();

    expect(fontScale.value).toBe('l');
    expect(contrast.value).toBe(true);
    expect(underlineLinks.value).toBe(false);
    expect(reduceMotion.value).toBe(true);
    expect(document.documentElement.classList.contains('a11y-contrast')).toBe(true);
    expect(document.documentElement.classList.contains('reduce-motion')).toBe(true);
  });

  it('reset returns every preference and the root element to default', async () => {
    const { useAccessibilityPreferences } = await loadFresh();
    const { fontScale, contrast, reduceMotion, isDefault, reset } = useAccessibilityPreferences();

    fontScale.value = 's';
    contrast.value = true;
    reduceMotion.value = true;
    await nextTick();

    reset();
    await nextTick();

    expect(isDefault.value).toBe(true);
    expect(document.documentElement.classList.contains('a11y-contrast')).toBe(false);
    expect(document.documentElement.classList.contains('reduce-motion')).toBe(false);
    expect(document.documentElement.style.getPropertyValue('--a11y-font-scale')).toBe('1');
    expect(document.documentElement.dataset.a11yFontScale).toBe('m');
  });

  it('falls back to defaults when stored data is unusable', async () => {
    // A private-mode browser, cleared site data, or a value written by an older shape.
    localStorage.setItem('vusa-a11y', 'not json');

    const { useAccessibilityPreferences } = await loadFresh();
    const { fontScale, reduceMotion, isDefault } = useAccessibilityPreferences();

    expect(fontScale.value).toBe('m');
    expect(reduceMotion.value).toBe(false);
    expect(isDefault.value).toBe(true);
  });

  it('ignores a stored font scale that is no longer a known size', async () => {
    localStorage.setItem('vusa-a11y', JSON.stringify({ fontScale: 'gigantic' }));

    const { useAccessibilityPreferences } = await loadFresh();

    expect(useAccessibilityPreferences().fontScale.value).toBe('m');
  });

  it('uses the current preference synchronously before persistence', async () => {
    const { isReducedMotionActive, useAccessibilityPreferences } = await loadFresh();
    const { reduceMotion } = useAccessibilityPreferences();

    expect(isReducedMotionActive()).toBe(false);

    reduceMotion.value = true;
    expect(isReducedMotionActive()).toBe(true);

    reduceMotion.value = false;
    expect(isReducedMotionActive()).toBe(false);
  });

  it.each([
    [false, false, false],
    [false, true, true],
    [true, false, true],
    [true, true, true],
  ])('combines app %s and OS %s motion preferences as %s', async (app, os, expected) => {
    localStorage.setItem('vusa-a11y', JSON.stringify({ reduceMotion: app }));
    Object.assign(motionQuery, { matches: os });
    const { useAccessibilityPreferences, isReducedMotionActive } = await loadFresh();
    const { isReducedMotion } = useAccessibilityPreferences();

    expect(isReducedMotion.value).toBe(expected);
    expect(isReducedMotionActive()).toBe(expected);
  });

  it('follows OS preference changes and still respects them after reset', async () => {
    const { useAccessibilityPreferences, isReducedMotionActive } = await loadFresh();
    const { isReducedMotion, reduceMotion, reset } = useAccessibilityPreferences();

    Object.assign(motionQuery, { matches: true });
    motionQuery.dispatchEvent(Object.assign(new Event('change'), { matches: true }));
    await nextTick();

    expect(isReducedMotion.value).toBe(true);
    expect(isReducedMotionActive()).toBe(true);
    reduceMotion.value = true;
    reset();
    expect(reduceMotion.value).toBe(false);
    expect(isReducedMotion.value).toBe(true);
    expect(isReducedMotionActive()).toBe(true);

    Object.assign(motionQuery, { matches: false });
    motionQuery.dispatchEvent(Object.assign(new Event('change'), { matches: false }));
    await nextTick();

    expect(isReducedMotion.value).toBe(false);
    expect(isReducedMotionActive()).toBe(false);
  });

  it('persists reset preferences across reloads', async () => {
    localStorage.setItem('vusa-a11y', JSON.stringify({ reduceMotion: true }));
    const { useAccessibilityPreferences } = await loadFresh();
    useAccessibilityPreferences().reset();
    await nextTick();

    const reloaded = await loadFresh();

    expect(reloaded.useAccessibilityPreferences().reduceMotion.value).toBe(false);
    expect(reloaded.isReducedMotionActive()).toBe(false);
  });

  it('keeps preferences usable in memory when storage operations fail', async () => {
    vi.spyOn(localStorage, 'getItem').mockImplementation(() => {
      throw new DOMException('Blocked', 'SecurityError');
    });
    vi.spyOn(localStorage, 'setItem').mockImplementation(() => {
      throw new DOMException('Blocked', 'SecurityError');
    });
    const { useAccessibilityPreferences, isReducedMotionActive } = await loadFresh();
    const preferences = useAccessibilityPreferences();

    expect(preferences.isDefault.value).toBe(true);
    preferences.reduceMotion.value = true;
    await nextTick();

    expect(isReducedMotionActive()).toBe(true);
    expect(document.documentElement.classList.contains('reduce-motion')).toBe(true);
  });

  it('starts when accessing localStorage itself is forbidden', async () => {
    const storage = window.localStorage;
    Object.defineProperty(window, 'localStorage', {
      configurable: true,
      get: () => { throw new DOMException('Blocked', 'SecurityError'); },
    });

    try {
      const { useAccessibilityPreferences } = await loadFresh();
      const preferences = useAccessibilityPreferences();
      expect(preferences.isDefault.value).toBe(true);
      preferences.reduceMotion.value = true;
      await nextTick();
      expect(document.documentElement.classList.contains('reduce-motion')).toBe(true);
    }
    finally {
      Object.defineProperty(window, 'localStorage', { configurable: true, writable: true, value: storage });
    }
  });
});
