import { trans as $t } from 'laravel-vue-i18n';

import type { InstitutionActivityStatus } from '@/Types/InstitutionActivity';

interface DateFormatters {
  /** A short day, e.g. "rugsėjo 15". */
  day: (value: string) => string;
  /** A full day with the year, e.g. "2026 m. rugpjūčio 30 d.". */
  fullDay: (value: string) => string;
}

/**
 * What is known about a body's meetings, in one line: what is already scheduled comes before
 * what is overdue. A body can hold both an upcoming meeting and an active check-in, and
 * showing only one of them was what made the list look wrong ("no meetings until October"
 * beside a meeting next Tuesday).
 */
export function describeInstitutionActivity(status: InstitutionActivityStatus, dates: DateFormatters): string {
  const parts: string[] = [];

  if (status.next_meeting_at) {
    parts.push($t('action_window.institution.next_meeting', { date: dates.day(status.next_meeting_at) }));
  }

  if (status.active_check_in_until) {
    parts.push(parts.length > 0
      ? $t('action_window.institution.check_in_until_short', { date: dates.day(status.active_check_in_until) })
      : $t('action_window.institution.check_in_until', { date: dates.day(status.active_check_in_until) }));
  }

  if (parts.length > 0) {
    return parts.join(' · ');
  }

  // The date, not `effective_days_since_activity`: that counter skips vacation periods,
  // so rendering it as "N days ago" told the reader something no calendar agrees with.
  if (status.last_meeting_at) {
    return $t('action_window.institution.last_meeting', { date: dates.fullDay(status.last_meeting_at) });
  }

  return $t('action_window.institution.no_meetings_yet');
}
