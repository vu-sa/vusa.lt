import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';

import { formatDateWith } from '@/Utils/dateTime';
import { changeDutyNameEndings } from '@/Utils/String';
import type { CommentAuthorDuty } from '@/Types/discussions';

interface DutyHolder {
  name: string | null;
  pronouns?: Record<string, string> | null;
}

/** The duty name as its holder reads it: gendered in Lithuanian unless the term keeps the original. */
export function authorDutyName(duty: CommentAuthorDuty, holder: DutyHolder | null | undefined): string {
  // A duty deleted since, or without a name in this language, arrives as null.
  if (!duty.name) return '';
  return changeDutyNameEndings(holder, duty.name, getActiveLanguage(), holder?.pronouns, duty.use_original_duty_name);
}

export function authorDutyLabel(duty: CommentAuthorDuty, holder: DutyHolder | null | undefined): string {
  return [authorDutyName(duty, holder), duty.tenant ?? duty.institution].filter(Boolean).join(' · ');
}

const monthOf = (date: string) => formatDateWith(date, { year: 'numeric', month: '2-digit' }, { locale: getActiveLanguage() });

/** "2024-09 – 2025-06", or "nuo 2025-09" while the term runs. */
export function authorDutyPeriod(duty: CommentAuthorDuty): string {
  if (!duty.start_date) return '';
  return duty.end_date
    ? $t('discussions.author_duty.period', { from: monthOf(duty.start_date), to: monthOf(duty.end_date) })
    : $t('discussions.author_duty.since', { from: monthOf(duty.start_date) });
}

/** A duty that had already ended when the comment was written is dated, so readers know it is a former role. */
export function endedBefore(duty: CommentAuthorDuty, writtenAt: string | null): boolean {
  return Boolean(duty.end_date && writtenAt && duty.end_date < writtenAt.slice(0, 10));
}
