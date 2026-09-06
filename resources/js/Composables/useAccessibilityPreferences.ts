import { computed, ref, watch } from 'vue';

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

const DEFAULTS: AccessibilityPreferences = {
  fontScale: 'm',
  contrast: false,
  underlineLinks: false,
};

const fontScale = ref<FontScaleKey>(DEFAULTS.fontScale);
const contrast = ref(DEFAULTS.contrast);
const underlineLinks = ref(DEFAULTS.underlineLinks);

let initialised = false;

function readStored(): Partial<AccessibilityPreferences> {
  if (typeof localStorage === 'undefined') {
    return {};
  }

  try {
    const raw = localStorage.getItem(STORAGE_KEY);

    return raw ? JSON.parse(raw) as Partial<AccessibilityPreferences> : {};
  }
  catch {
    // Private mode, cleared site data, or a value from an older shape. A reader who cannot
    // persist preferences should still get working controls for this visit.
    return {};
  }
}

function persist(): void {
  if (typeof localStorage === 'undefined') {
    return;
  }

  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      fontScale: fontScale.value,
      contrast: contrast.value,
      underlineLinks: underlineLinks.value,
    }));
  }
  catch {
    // Storage full or blocked — the preference still applies for this page view.
  }
}

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

export function useAccessibilityPreferences() {
  if (!initialised) {
    initialised = true;

    const stored = readStored();

    if (stored.fontScale && stored.fontScale in FONT_SCALES) {
      fontScale.value = stored.fontScale;
    }
    contrast.value = stored.contrast ?? DEFAULTS.contrast;
    underlineLinks.value = stored.underlineLinks ?? DEFAULTS.underlineLinks;

    apply();

    watch([fontScale, contrast, underlineLinks], () => {
      apply();
      persist();
    });
  }

  /** Drives the "preferences are active" cue on the trigger button. */
  const isDefault = computed(() => fontScale.value === DEFAULTS.fontScale
    && contrast.value === DEFAULTS.contrast
    && underlineLinks.value === DEFAULTS.underlineLinks);

  function reset(): void {
    fontScale.value = DEFAULTS.fontScale;
    contrast.value = DEFAULTS.contrast;
    underlineLinks.value = DEFAULTS.underlineLinks;
  }

  return { fontScale, contrast, underlineLinks, isDefault, reset };
}
