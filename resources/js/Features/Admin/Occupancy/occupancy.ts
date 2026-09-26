import { todayIso } from '@/Utils/dateTime';

export type TermStatus = 'upcoming' | 'current' | 'ended';

interface TermDates {
  start_date?: string | null;
  end_date?: string | null;
}

const day = (value?: string | null): string | null => (value ? String(value).slice(0, 10) : null);

/**
 * Where a term sits relative to today. Mirrors `Duty::current_users()`: a term whose end
 * date is today no longer counts, and one that has not started yet is not current.
 */
export function termStatus(term: TermDates, today: string = todayIso()): TermStatus {
  const start = day(term.start_date);
  const end = day(term.end_date);

  if (start && start > today) {
    return 'upcoming';
  }

  if (end && end <= today) {
    return 'ended';
  }

  return 'current';
}
