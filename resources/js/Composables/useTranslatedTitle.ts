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
 * Type-safe helper for accessing translated field values
 */
export function getTranslatedValue(
  translatedField: any,
  locale?: string,
): string {
  const page = usePage();
  const currentLocale = locale || page.props.app.locale as 'lt' | 'en';

  if (typeof translatedField === 'object' && translatedField !== null && currentLocale in translatedField) {
    return translatedField[currentLocale];
  }

  if (typeof translatedField === 'string') {
    return translatedField;
  }

  return '';
}
