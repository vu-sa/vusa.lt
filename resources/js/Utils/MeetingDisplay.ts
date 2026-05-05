import { LocaleEnum } from '@/Types/enums';
import { MeetingType } from '@/Types/MeetingType';
import { formatStaticTime } from '@/Utils/IntlTime';

type MeetingTypeLike
  = | string
    | null
    | undefined
    | { value?: string | null };

interface MeetingLike {
  start_time: string | Date;
  type?: MeetingTypeLike;
}

const TIME_KEYS: Array<keyof Intl.DateTimeFormatOptions> = [
  'hour',
  'minute',
  'second',
  'hour12',
  'timeStyle',
  'dayPeriod',
  'fractionalSecondDigits',
];

export function isEmailMeeting(meeting: Pick<MeetingLike, 'type'> | null | undefined): boolean {
  const type = meeting?.type;
  if (!type) return false;
  if (typeof type === 'string') return type === MeetingType.Email;
  return type.value === MeetingType.Email;
}

function stripTimeKeys(opts: Intl.DateTimeFormatOptions): Intl.DateTimeFormatOptions {
  const out: Intl.DateTimeFormatOptions = { ...opts };
  for (const key of TIME_KEYS) {
    delete out[key];
  }
  return out;
}

/**
 * Format a meeting's start_time with date + time, except for email-type
 * meetings where the time is a 23:59 deadline marker — those render
 * date-only to avoid misleading users.
 */
export function formatMeetingDateTime(
  meeting: MeetingLike,
  opts: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  },
  locale: LocaleEnum = LocaleEnum.LT,
): string {
  const finalOpts = isEmailMeeting(meeting) ? stripTimeKeys(opts) : opts;
  return formatStaticTime(new Date(meeting.start_time), finalOpts, locale);
}

/**
 * Returns a HH:MM string for non-email meetings, or null when there is no
 * meaningful time to show. Callers can `v-if` on the return value.
 */
export function formatMeetingTimeOnly(
  meeting: MeetingLike,
  locale: LocaleEnum = LocaleEnum.LT,
): string | null {
  if (isEmailMeeting(meeting)) return null;
  return formatStaticTime(
    new Date(meeting.start_time),
    { hour: '2-digit', minute: '2-digit' },
    locale,
  );
}
