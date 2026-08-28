import { trans as $t } from 'laravel-vue-i18n';

/**
 * How long an assignment ran, in at most two units.
 *
 * The range is genuinely wide — a stand-in lasts days, a long-serving member a decade — so
 * a single unit is either meaningless ("0 years") or unreadable ("134 months"). Two units,
 * largest first, reads the same at both ends and never grows past a handful of characters.
 *
 * Months are counted calendar-wise rather than as 30-day blocks: a term running 07-01 →
 * 06-30 is eleven months and twenty-nine days of arithmetic, and one year to everyone who
 * reads it.
 */
export function formatDuration(start: Date, end: Date | null): string {
  const to = end ?? new Date();

  if (to < start) return '—';

  let months = (to.getFullYear() - start.getFullYear()) * 12 + (to.getMonth() - start.getMonth());
  let days = to.getDate() - start.getDate();

  if (days < 0) {
    months -= 1;
    // Days left over after the last whole month, measured against that month's own length.
    days += new Date(to.getFullYear(), to.getMonth(), 0).getDate();
  }

  // Inclusive of both endpoints: a seat held 05-18 → 05-18 lasted a day, not none.
  days += 1;

  const years = Math.floor(months / 12);
  const restMonths = months % 12;

  if (years > 0) {
    return restMonths > 0
      ? `${unit('year', years)} ${unit('month', restMonths)}`
      : unit('year', years);
  }

  if (months > 0) {
    return days > 1 ? `${unit('month', months)} ${unit('day', days)}` : unit('month', months);
  }

  return unit('day', days);
}

/** `2 m.`, `4 mėn.`, `18 d.` — the abbreviations live in the translation files. */
function unit(key: 'year' | 'month' | 'day', value: number): string {
  return $t(`dutiables.timeline.duration.${key}`, { value: String(value) });
}
