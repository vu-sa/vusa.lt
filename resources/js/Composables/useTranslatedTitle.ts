import { usePage } from '@inertiajs/vue3';

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

/** Read flattened search fields without changing the identifiers used by facets and scoped keys. */
export function getSearchTranslation(document: object, field: string, locale?: string, fallback = ''): string {
  const values = document as Record<string, unknown>;
  const legacy = values[field];
  return getTranslatedValue({ lt: values[`${field}_lt`], en: values[`${field}_en`] }, locale,
    typeof legacy === 'string' ? legacy : fallback);
}

export function getSearchTranslations(document: object, field: string, locale?: string): string[] {
  const values = document as Record<string, unknown>;
  const asList = (value: unknown): unknown[] => Array.isArray(value) ? value : [];
  const lt = asList(values[`${field}_lt`]);
  const en = asList(values[`${field}_en`]);
  const legacy = asList(values[field]);
  const hasTranslations = lt.length > 0 || en.length > 0;

  return Array.from({ length: Math.max(lt.length, en.length, legacy.length) }, (_, index) => {
    const stored = legacy[index];
    return getTranslatedValue({ lt: lt[index], en: en[index] }, locale,
      !hasTranslations && typeof stored === 'string' ? stored : '');
  }).filter(value => value.trim() !== '');
}
