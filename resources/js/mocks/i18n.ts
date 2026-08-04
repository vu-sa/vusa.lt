/**
 * Unified i18n mock for Storybook, VitePress, and Vitest
 *
 * Uses actual generated translations from lang/*.json files.
 * Exports both plain functions (for VitePress) and fn()-wrapped versions (for Storybook).
 */

// Import actual generated translations using relative paths from this file
import ltJson from '../../../lang/lt.json';
import phpAdminLt from '../../../lang/php_admin_lt.json';
import phpPublicLt from '../../../lang/php_public_lt.json';

// Merge translations - admin overrides base
const translations: Record<string, string> = {
  ...(ltJson as Record<string, string>),
  ...(phpPublicLt as Record<string, string>),
  ...(phpAdminLt as Record<string, string>),
};

/**
 * Get a translation by key with optional parameter replacement.
 * Falls back to the key itself if translation is not found.
 */
export function trans(key: string, replace: Record<string, string | number> = {}): string {
  let translation = translations[key] ?? key;

  // Handle Laravel-style :parameter replacements
  if (replace && typeof replace === 'object') {
    Object.entries(replace).forEach(([replaceKey, value]) => {
      translation = translation.replace(`:${replaceKey}`, String(value));
    });
  }

  return translation;
}

/**
 * Alias for trans() - used in some components
 */
export const wTrans = trans;

/**
 * Alias for $t() - commonly used in Vue templates
 */
export const $t = trans;

/**
 * Resolve a Laravel choice string against a count.
 *
 * Handles both the simple `singular|plural` form and the explicit-condition form
 * (`{1} stovykla|[2,9] stovyklos|[10,*] stovyklų`) that Lithuanian needs, because its
 * noun form depends on the last digits of the number rather than on one-versus-many.
 */
function resolveChoice(message: string, count: number): string {
  const segments = message.split('|');
  const plainSegments: string[] = [];

  for (const segment of segments) {
    const conditioned = segment.match(/^\s*(\{([^}]*)\}|\[([^\]]*)\])(.*)$/s);

    if (!conditioned) {
      plainSegments.push(segment);
      continue;
    }

    const [, , exact, range, text] = conditioned;

    if (exact !== undefined && Number(exact) === count) {
      return text.trim();
    }

    if (range !== undefined) {
      const [from, to] = range.split(',').map(part => part.trim());
      const lower = Number(from);
      const upper = to === '*' ? Infinity : Number(to);

      if (count >= lower && count <= upper) {
        return text.trim();
      }
    }
  }

  if (plainSegments.length === 0) {
    return message;
  }

  return (count === 1 ? plainSegments[0] : plainSegments[1] ?? plainSegments[0]).trim();
}

/**
 * Plural translation function.
 * Supports Laravel's pipe-separated plural syntax: "one item|:count items"
 */
export function transChoice(key: string, count: number, replace: Record<string, string | number> = {}): string {
  let translation: string = translations[key] ?? key;

  // Handle pipe-separated plurals (Laravel style)
  if (translation.includes('|')) {
    translation = resolveChoice(translation, count);
  }

  // Always include count in replacements
  const allReplacements = { count, ...replace };

  Object.entries(allReplacements).forEach(([replaceKey, value]) => {
    translation = translation.replace(`:${replaceKey}`, String(value));
  });

  return translation;
}

/**
 * Alias for transChoice() - used in some components
 */
export const wTransChoice = transChoice;

/**
 * Alias for $tChoice() - commonly used in Vue templates
 */
export const $tChoice = transChoice;

// ============================================================================
// laravel-vue-i18n API surface
//
// `.storybook/main.ts` aliases `laravel-vue-i18n` to this file, so anything a
// component imports from that package has to exist here too. Without the alias a
// `<script setup>` binding like `import { trans as $t } from 'laravel-vue-i18n'`
// shadows the `$t` global that `preview.ts` installs, and every story renders raw
// translation keys instead of copy — which also throws off layout review, since an
// untranslated key is a long unbreakable string.
// ============================================================================

/**
 * The mock only bundles the Lithuanian catalogue (see the imports at the top), so this
 * is the language every story renders in.
 */
export function getActiveLanguage(): string {
  return 'lt';
}

/** No-op: there is nothing to fetch, the catalogue is bundled at build time. */
export function loadLanguageAsync(_lang?: string): Promise<void> {
  return Promise.resolve();
}

/** No-op, mirroring `loadLanguageAsync`. */
export function loadLanguage(_lang?: string): void {
  // Intentionally empty — translations are already resolved synchronously.
}

/** Always true: the bundled catalogue needs no loading step. */
export function isLoaded(_lang?: string): boolean {
  return true;
}

/**
 * Stand-in for the real Vue plugin. Storybook never boots `admin.ts`/`public.ts`, so
 * this exists only so an `import { i18nVue }` resolves; installing it registers the
 * same globals `preview.ts` sets up.
 */
export const i18nVue = {
  install(app: { config: { globalProperties: Record<string, unknown> } }) {
    app.config.globalProperties.$t = trans;
    app.config.globalProperties.$tChoice = transChoice;
  },
};

// ============================================================================
// Storybook fn()-wrapped versions for spy/assertion capabilities
// ============================================================================

const transFn: typeof trans = trans;
const transChoiceFn: typeof transChoice = transChoice;

// Try to wrap with storybook/test fn() for spy capabilities
// This will silently fail in VitePress where storybook/test is not available
try {
  // Dynamic import would be ideal but we need sync exports
  // Instead, check if we're in a Storybook environment
  if (typeof window !== 'undefined' && (window as any).__STORYBOOK_PREVIEW__) {
    // We're in Storybook - the fn() wrapped versions will be set up via preview.ts
    // This block is a placeholder for future dynamic setup if needed
  }
}
catch {
  // Not in Storybook environment, use plain functions
}

export { transFn, transChoiceFn };

// Default export for convenience
export default {
  trans,
  wTrans,
  transChoice,
  wTransChoice,
  $t,
  $tChoice,
  getActiveLanguage,
  loadLanguageAsync,
  loadLanguage,
  isLoaded,
  i18nVue,
  transFn,
  transChoiceFn,
  // Export raw translations for debugging/inspection
  translations,
};
