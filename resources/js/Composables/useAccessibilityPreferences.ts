import { computed, watch } from 'vue';
import { usePreferredReducedMotion, useStorage } from '@vueuse/core';

// Module state keeps public and admin controls in sync.

const STORAGE_KEY = 'vusa-a11y';

/** Multipliers for `--a11y-font-scale`, applied to the root font size. */
export const FONT_SCALES = { s: 0.9, m: 1, l: 1.15, xl: 1.3 } as const;

export type FontScaleKey = keyof typeof FONT_SCALES;

interface AccessibilityPreferences {
  fontScale: FontScaleKey;
  contrast: boolean;
  underlineLinks: boolean;
  reduceMotion: boolean;
}

const DEFAULTS: AccessibilityPreferences = Object.freeze({
  fontScale: 'm',
  contrast: false,
  underlineLinks: false,
  reduceMotion: false,
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

if (typeof preferences.value.reduceMotion !== 'boolean') {
  preferences.value.reduceMotion = DEFAULTS.reduceMotion;
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

const reduceMotion = computed<boolean>({
  get: () => preferences.value.reduceMotion ?? DEFAULTS.reduceMotion,
  set: (val) => { preferences.value.reduceMotion = val; },
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
  root.classList.toggle('reduce-motion', reduceMotion.value);
}

apply();
watch([fontScale, contrast, underlineLinks, reduceMotion], apply);

/** Navigation defaults need the current preference before storage watchers flush. */
export function isReducedMotionActive(): boolean {
  if (typeof window === 'undefined') return false;

  return reduceMotion.value || (window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches ?? false);
}

export function preferredScrollBehavior(): ScrollBehavior {
  return isReducedMotionActive() ? 'instant' : 'smooth';
}

export function useAccessibilityPreferences() {
  const preferredReducedMotion = usePreferredReducedMotion();

  /** Unified motion preference: true if user enabled it in-app OR if OS requests reduced motion. */
  const isReducedMotion = computed<boolean>(() => reduceMotion.value || preferredReducedMotion.value === 'reduce');

  /** Drives the "preferences are active" cue on the trigger button. */
  const isDefault = computed(() => fontScale.value === DEFAULTS.fontScale
    && contrast.value === DEFAULTS.contrast
    && underlineLinks.value === DEFAULTS.underlineLinks
    && reduceMotion.value === DEFAULTS.reduceMotion);

  function reset(): void {
    preferences.value = { ...DEFAULTS };
  }

  return { fontScale, contrast, underlineLinks, reduceMotion, isReducedMotion, isDefault, reset };
}
