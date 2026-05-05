/**
 * Unified i18n mock for Storybook, VitePress, and Vitest
 *
 * Uses actual generated translations from lang/*.json files.
 * Exports both plain functions (for VitePress) and fn()-wrapped versions (for Storybook).
 */

// Import actual generated translations using relative paths from this file
import ltJson from '../../../lang/lt.json';
import phpAdminLt from '../../../lang/php_admin_lt.json';

// Merge translations - admin overrides base
const translations: Record<string, string> = {
  ...(ltJson as Record<string, string>),
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
 * Plural translation function.
 * Supports Laravel's pipe-separated plural syntax: "one item|:count items"
 */
export function transChoice(key: string, count: number, replace: Record<string, string | number> = {}): string {
  let translation: string = translations[key] ?? key;

  // Handle pipe-separated plurals (Laravel style)
  if (translation.includes('|')) {
    const [singular, plural] = translation.split('|');
    translation = count === 1 ? (singular ?? key) : (plural ?? singular ?? key);
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
  transFn,
  transChoiceFn,
  // Export raw translations for debugging/inspection
  translations,
};
