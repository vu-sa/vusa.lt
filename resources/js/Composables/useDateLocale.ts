import { usePage } from '@inertiajs/vue3';
import type { Locale } from 'date-fns';
import { enUS, lt } from 'date-fns/locale';
import { computed, type ComputedRef } from 'vue';

/**
 * date-fns needs its own locale object, which every component used to derive with its own
 * `locale === 'lt' ? lt : enUS` ternary. Import from here instead so a third locale (or a
 * different default) is one edit rather than a dozen.
 */
export function dateLocaleFor(locale: string | undefined | null): Locale {
  return locale === 'en' ? enUS : lt;
}

/** The date-fns locale of the current page. */
export function useDateLocale(): ComputedRef<Locale> {
  const page = usePage();

  return computed(() => dateLocaleFor(page.props.app?.locale));
}
