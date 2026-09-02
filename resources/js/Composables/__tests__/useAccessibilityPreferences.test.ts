import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';

/**
 * The composable keeps module-level state on purpose (the header menu and the mobile drawer both
 * render controls and must agree), so each test re-imports it fresh rather than sharing one
 * instance.
 */
async function loadFresh() {
  vi.resetModules();

  return import('../useAccessibilityPreferences');
}

beforeEach(() => {
  localStorage.clear();
  document.documentElement.className = '';
  document.documentElement.removeAttribute('style');
});

describe('useAccessibilityPreferences', () => {
  it('starts at the defaults and applies a neutral scale', async () => {
    const { useAccessibilityPreferences } = await loadFresh();
    const { fontScale, contrast, underlineLinks, isDefault } = useAccessibilityPreferences();

    expect(fontScale.value).toBe('m');
    expect(contrast.value).toBe(false);
    expect(underlineLinks.value).toBe(false);
    expect(isDefault.value).toBe(true);
    expect(document.documentElement.style.getPropertyValue('--a11y-font-scale')).toBe('1');
  });

  it('drives the root element and persists when a preference changes', async () => {
    const { useAccessibilityPreferences, FONT_SCALES } = await loadFresh();
    const { fontScale, contrast, underlineLinks, isDefault } = useAccessibilityPreferences();

    fontScale.value = 'xl';
    contrast.value = true;
    underlineLinks.value = true;
    await nextTick();

    expect(document.documentElement.style.getPropertyValue('--a11y-font-scale'))
      .toBe(String(FONT_SCALES.xl));
    expect(document.documentElement.classList.contains('a11y-contrast')).toBe(true);
    expect(document.documentElement.classList.contains('a11y-underline')).toBe(true);
    expect(isDefault.value).toBe(false);

    expect(JSON.parse(localStorage.getItem('vusa-a11y') as string)).toEqual({
      fontScale: 'xl',
      contrast: true,
      underlineLinks: true,
    });
  });

  it('restores stored preferences on a later visit', async () => {
    localStorage.setItem('vusa-a11y', JSON.stringify({
      fontScale: 'l', contrast: true, underlineLinks: false,
    }));

    const { useAccessibilityPreferences } = await loadFresh();
    const { fontScale, contrast, underlineLinks } = useAccessibilityPreferences();

    expect(fontScale.value).toBe('l');
    expect(contrast.value).toBe(true);
    expect(underlineLinks.value).toBe(false);
    expect(document.documentElement.classList.contains('a11y-contrast')).toBe(true);
  });

  it('reset returns every preference and the root element to default', async () => {
    const { useAccessibilityPreferences } = await loadFresh();
    const { fontScale, contrast, isDefault, reset } = useAccessibilityPreferences();

    fontScale.value = 's';
    contrast.value = true;
    await nextTick();

    reset();
    await nextTick();

    expect(isDefault.value).toBe(true);
    expect(document.documentElement.classList.contains('a11y-contrast')).toBe(false);
    expect(document.documentElement.style.getPropertyValue('--a11y-font-scale')).toBe('1');
  });

  it('falls back to defaults when stored data is unusable', async () => {
    // A private-mode browser, cleared site data, or a value written by an older shape.
    localStorage.setItem('vusa-a11y', 'not json');

    const { useAccessibilityPreferences } = await loadFresh();
    const { fontScale, isDefault } = useAccessibilityPreferences();

    expect(fontScale.value).toBe('m');
    expect(isDefault.value).toBe(true);
  });

  it('ignores a stored font scale that is no longer a known size', async () => {
    localStorage.setItem('vusa-a11y', JSON.stringify({ fontScale: 'gigantic' }));

    const { useAccessibilityPreferences } = await loadFresh();

    expect(useAccessibilityPreferences().fontScale.value).toBe('m');
  });
});
