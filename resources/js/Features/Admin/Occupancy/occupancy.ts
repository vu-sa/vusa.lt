import { todayIso } from '@/Utils/dateTime';

export type TermStatus = 'upcoming' | 'current' | 'ended';

interface TermDates {
  start_date?: string | null;
  end_date?: string | null;
}

const day = (value?: string | null): string | null => (value ? String(value).slice(0, 10) : null);

// Both dates include the whole calendar day, matching the server.
export function termStatus(term: TermDates, today: string = todayIso()): TermStatus {
  const start = day(term.start_date);
  const end = day(term.end_date);

  if (start && start > today) {
    return 'upcoming';
  }

  if (end && end < today) {
    return 'ended';
  }

  return 'current';
}

export interface FormerDutyName {
  name: string;
  used_until: string;
}

/** The name a duty carried on a day; mirrors `Duty::nameOn()` so an old term reads as it did then. */
export function dutyNameOn(current: string, formerNames: FormerDutyName[] | null | undefined, date?: string | null): string {
  const onDay = day(date);

  if (!onDay || !formerNames?.length) {
    return current;
  }

  const inForce = [...formerNames]
    .sort((a, b) => String(a.used_until).localeCompare(String(b.used_until)))
    .find(former => (day(former.used_until) ?? '') >= onDay);

  return inForce?.name || current;
}

/** Curators (and their English-programme counterpart, mentors) are the duties a study programme belongs to. */
export const CURATOR_TYPE_SLUGS = ['kuratoriai', 'mentors'];

export function isCuratorDuty(typeSlugs: (string | null | undefined)[] | null | undefined): boolean {
  return (typeSlugs ?? []).some(slug => !!slug && CURATOR_TYPE_SLUGS.includes(slug));
}

/**
 * The years a person held any duty: "2019–2021", "2023–dabar", or one year. Null without dated terms.
 * `presentLabel` is passed in so this stays free of i18n.
 */
export function termYearSpan(terms: TermDates[], presentLabel: string, today: string = todayIso()): string | null {
  const starts = terms.map(term => day(term.start_date)).filter((start): start is string => !!start);

  if (starts.length === 0) {
    return null;
  }

  const first = starts.sort()[0]!.slice(0, 4);

  if (terms.some(term => termStatus(term, today) !== 'ended')) {
    return `${first}–${presentLabel}`;
  }

  const last = terms.map(term => day(term.end_date) ?? '').sort().at(-1)!.slice(0, 4);

  return first === last ? first : `${first}–${last}`;
}
