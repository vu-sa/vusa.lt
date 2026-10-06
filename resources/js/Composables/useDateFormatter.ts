import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import {
  formatDate,
  formatDateFact,
  formatDateTime,
  formatNearDate,
  formatTime,
  type DateInput,
  type FormatDateOptions,
  type FormatDateTimeOptions,
  type FormatNearOptions,
  type FormatTimeOptions,
} from '@/Utils/dateTime';

/**
 * Composable providing reactive date formatters bound to the current page locale.
 */
export function useDateFormatter() {
  const page = usePage();
  const locale = computed(() => (page?.props?.app as { locale?: string } | undefined)?.locale ?? 'lt');

  return {
    locale,
    formatDate: (input: DateInput, options?: FormatDateOptions) =>
      formatDate(input, { locale: locale.value, ...options }),
    formatTime: (input: DateInput, options?: FormatTimeOptions) =>
      formatTime(input, { locale: locale.value, ...options }),
    formatDateTime: (input: DateInput, options?: FormatDateTimeOptions) =>
      formatDateTime(input, { locale: locale.value, ...options }),
    formatNearDate: (input: DateInput, options?: FormatNearOptions) =>
      formatNearDate(input, { locale: locale.value, ...options }),
    formatDateFact: (input: DateInput, options?: FormatNearOptions) =>
      formatDateFact(input, { locale: locale.value, ...options }),
  };
}
