import { computed, watch } from 'vue';
import { useStorage } from '@vueuse/core';

/**
 * Reader preferences for the public site: text size, high contrast, forced link underlines.
 *
 * The CSS they drive lives in `resources/css/app.css`
 * (the "Accessibility preferences" block) and is scoped to `html[data-surface="public"]`, so the
 * admin interface — which sets its own root font size — is unaffected.
 *
 * State is module-level rather than per-component: the header menu and the mobile drawer both
 * render a control, and two independent copies would disagree about what is currently on.
 */

const STORAGE_KEY = 'vusa-a11y';

/** Multipliers for `--a11y-font-scale`, applied to the root font size. */
export const FONT_SCALES = { s: 0.9, m: 1, l: 1.15, xl: 1.3 } as const;

export type FontScaleKey = keyof typeof FONT_SCALES;

interface AccessibilityPreferences {
  fontScale: FontScaleKey;
  contrast: boolean;
  underlineLinks: boolean;
}

const DEFAULTS: AccessibilityPreferences = Object.freeze({
  fontScale: 'm',
  contrast: false,
  underlineLinks: false,
});

const preferences = useStorage<AccessibilityPreferences>(
  STORAGE_KEY,
  () => ({ ...DEFAULTS }),
  undefined,
  { mergeDefaults: true, onError: () => {} },
);

if (typeof preferences.value !== 'object' || preferences.value === null) {
  preferences.value = { ...DEFAULTS };
}

const fontScale = computed<FontScaleKey>({
  get: () => (preferences.value.fontScale in FONT_SCALES ? preferences.value.fontScale : DEFAULTS.fontScale),
  set: (val) => { preferences.value.fontScale = val; },
});

const contrast = computed<boolean>({
  get: () => preferences.value.contrast ?? DEFAULTS.contrast,
  set: (val) => { preferences.value.contrast = val; },
});

const underlineLinks = computed<boolean>({
  get: () => preferences.value.underlineLinks ?? DEFAULTS.underlineLinks,
  set: (val) => { preferences.value.underlineLinks = val; },
});

function apply(): void {
  if (typeof document === 'undefined') {
    return;
  }

  const root = document.documentElement;

  root.style.setProperty('--a11y-font-scale', String(FONT_SCALES[fontScale.value]));
  root.dataset.a11yFontScale = fontScale.value;
  root.classList.toggle('a11y-contrast', contrast.value);
  root.classList.toggle('a11y-underline', underlineLinks.value);
}

apply();
watch([fontScale, contrast, underlineLinks], apply);

export function useAccessibilityPreferences() {
  /** Drives the "preferences are active" cue on the trigger button. */
  const isDefault = computed(() => fontScale.value === DEFAULTS.fontScale
    && contrast.value === DEFAULTS.contrast
    && underlineLinks.value === DEFAULTS.underlineLinks);

  function reset(): void {
    preferences.value = { ...DEFAULTS };
  }

  return { fontScale, contrast, underlineLinks, isDefault, reset };
}
