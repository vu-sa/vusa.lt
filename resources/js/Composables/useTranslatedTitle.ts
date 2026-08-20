import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

/**
 * Composable for handling translatable model titles in edit pages
 * Handles both full translation objects and localized strings
 */
export function useTranslatedTitle(
  titleKey: string,
  modelName: ComputedRef<any> | any,
): ComputedRef<string> {
  const page = usePage();

  return computed(() => {
    const locale = page.props.app.locale as 'lt' | 'en';
    const nameValue = typeof modelName === 'object' && 'value' in modelName ? modelName.value : modelName;

    // Handle translation objects vs localized strings
    let displayName: string;
    if (typeof nameValue === 'object' && nameValue !== null && locale in nameValue) {
      displayName = nameValue[locale];
    }
    else if (typeof nameValue === 'string') {
      displayName = nameValue;
    }
    else {
      displayName = 'Unknown';
    }

    return $t(titleKey, { name: displayName });
  });
}

/**
 * Read a Spatie-translatable field (`{ lt: '…', en: '…' }`) in the current locale.
 *
 * Every caller used to re-implement its own fallback chain, and they disagreed on the order.
 * The chain here is: the requested locale → Lithuanian (the source language, always filled) →
 * the first non-empty translation → the `fallback`.
 *
 * @param locale overrides the page locale — pass it when rendering a specific language tab
 */
export function getTranslatedValue(
  translatedField: unknown,
  locale?: string,
  fallback = '',
): string {
  if (typeof translatedField === 'string') {
    return translatedField;
  }

  if (typeof translatedField !== 'object' || translatedField === null) {
    return fallback;
  }

  const translations = translatedField as Record<string, unknown>;
  const currentLocale = locale || (usePage().props.app?.locale as string) || 'lt';

  const candidates = [
    translations[currentLocale],
    translations.lt,
    ...Object.values(translations),
  ];

  const value = candidates.find(
    candidate => typeof candidate === 'string' && candidate.trim() !== '',
  );

  return (value as string | undefined) ?? fallback;
}
