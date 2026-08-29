/**
 * Dates in the window, in the language the app is actually running in.
 *
 * `toLocaleDateString()` follows the *browser's* locale, so a Lithuanian page rendered
 * a Lithuanian question above an English date. date-fns takes the locale explicitly,
 * which is also what the rest of the app already does ({@link useDateLocale}).
 */

import { format } from 'date-fns';

import { useDateLocale } from '@/Composables/useDateLocale';

export function useWindowDates() {
  const locale = useDateLocale();

  const at = (value: Date | string, pattern: string): string =>
    format(typeof value === 'string' ? new Date(value) : value, pattern, { locale: locale.value });

  return {
    /** "rugsėjo 15" */
    day: (value: Date | string) => at(value, 'MMMM d'),
    /** "sekmadienis, rugpjūčio 30" */
    dayWithWeekday: (value: Date | string) => at(value, 'EEEE, MMMM d'),
    /** "sekmadienis, rugpjūčio 30, 18:00" */
    dayWithTime: (value: Date | string) => at(value, 'EEEE, MMMM d, HH:mm'),
    /** "2026 m. rugpjūčio 30 d." */
    fullDay: (value: Date | string) => at(value, 'PPP'),
  };
}
