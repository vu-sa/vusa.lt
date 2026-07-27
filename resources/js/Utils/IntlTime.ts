import { LocaleEnum } from '@/Types/enums';

const MINUTE_MILISECONDS = 60 * 1000;
const HOUR_MILISECONDS = MINUTE_MILISECONDS * 60;
const DAY_MILISECONDS = HOUR_MILISECONDS * 24;

export const formatRelativeTime = (
  time: number | Date,
  dateTimeOptions: Intl.RelativeTimeFormatOptions = {
    numeric: 'auto',
  },
  // check locale against LocaleEnum
  locale: LocaleEnum = LocaleEnum.LT,
) => {
  const date = new Date(time);

  const rtf = new Intl.RelativeTimeFormat(locale, dateTimeOptions);

  const daysDifference = Math.round(
    (date.getTime() - new Date().getTime()) / DAY_MILISECONDS,
  );

  const hoursDifference = Math.round(
    (date.getTime() - new Date().getTime()) / HOUR_MILISECONDS,
  );

  const minutesDifference = Math.round(
    (date.getTime() - new Date().getTime()) / MINUTE_MILISECONDS,
  );

  const secondsDifference = Math.round(
    (date.getTime() - new Date().getTime()) / 1000,
  );

  if (daysDifference != 0) {
    return rtf.format(daysDifference, 'day');
  }
  else if (hoursDifference != 0) {
    return rtf.format(hoursDifference, 'hour');
  }
  else if (minutesDifference != 0) {
    return rtf.format(minutesDifference, 'minute');
  }
  else if (secondsDifference != 0) {
    return rtf.format(secondsDifference, 'second');
  }
  else {
    return locale === LocaleEnum.LT ? 'dabar' : 'now';
  }
};

export const formatStaticTime = (
  time: number | Date | undefined,
  dateTimeOptions: Intl.DateTimeFormatOptions = {
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
  },
  locale: LocaleEnum = LocaleEnum.LT,
) => {
  if (!time) return '';
  // make date of time
  const date = new Date(time);

  const staticTime = new Intl.DateTimeFormat(locale, dateTimeOptions).format(
    date,
  );

  return staticTime;
};

/**
 * Format the short month name, uppercased and without a trailing period
 * (e.g. a Lithuanian April date → "BAL"). Used for compact date badges.
 */
export const formatMonthShort = (
  time: number | Date | undefined,
  locale: LocaleEnum = LocaleEnum.LT,
): string => {
  if (!time) return '';

  return formatStaticTime(time, { month: 'short' }, locale)
    .replace(/\.$/, '')
    .toUpperCase();
};

export const getDaysDifference = (time: number | Date) => {
  const now = new Date();
  const difference = new Date(time);

  const daysDifference = Math.round(
    (now.getTime() - difference.getTime()) / DAY_MILISECONDS,
  );

  return daysDifference;
};

// Calendar-specific utility functions

/**
 * Check if two dates are on the same day
 */
export const isSameDay = (date1: Date, date2: Date): boolean => {
  return date1.getFullYear() === date2.getFullYear()
    && date1.getMonth() === date2.getMonth()
    && date1.getDate() === date2.getDate();
};

/**
 * Format date for calendar event display (month + day)
 */
export const formatEventDate = (
  time: number | Date,
  locale: LocaleEnum = LocaleEnum.LT,
): string => {
  return formatStaticTime(time, {
    month: 'short',
    day: 'numeric',
  }, locale);
};

/**
 * Format time only for calendar events
 */
export const formatEventTime = (
  time: number | Date,
  locale: LocaleEnum = LocaleEnum.LT,
): string => {
  return formatStaticTime(time, {
    hour: 'numeric',
    minute: 'numeric',
  }, locale);
};

/**
 * Format year for calendar events
 */
export const formatEventYear = (
  time: number | Date,
  locale: LocaleEnum = LocaleEnum.LT,
): string => {
  return formatStaticTime(time, {
    year: 'numeric',
  }, locale);
};

/**
 * Format date range for events with start and end dates
 */
export const formatDateRange = (
  startDate: Date,
  endDate?: Date,
  locale: LocaleEnum = LocaleEnum.LT,
): string => {
  if (!endDate) {
    return formatStaticTime(startDate, { dateStyle: 'medium', timeStyle: 'short' }, locale);
  }

  const start = formatStaticTime(startDate, { dateStyle: 'medium', timeStyle: 'short' }, locale);

  if (isSameDay(startDate, endDate)) {
    // Same day - show start time and end time only
    const endTime = formatEventTime(endDate, locale);
    return `${start} - ${endTime}`;
  }
  else {
    // Different days - show full date and time for both
    const end = formatStaticTime(endDate, { dateStyle: 'medium', timeStyle: 'short' }, locale);
    return `${start} - ${end}`;
  }
};

export interface EventDateSpan {
  /** The date part, e.g. "2026 m. rugpjūčio 25–27 d." */
  primary: string;
  /** The time part or duration hint, e.g. "10:00–18:00" or "3 dienos". */
  secondary: string;
}

/**
 * Render an event's start/end as a fluent, locale-correct span.
 *
 * Multi-day ranges go through `Intl.DateTimeFormat.formatRange`, which collapses
 * shared components the way each locale expects ("2026 m. rugpjūčio 25–27 d.")
 * instead of concatenating two full dates. Falls back to `formatDateRange` where
 * `formatRange` is unavailable.
 */
export const formatEventDateSpan = (
  start: number | Date | string,
  end?: number | Date | string | null,
  options: { allDay?: boolean; locale?: LocaleEnum } = {},
): EventDateSpan => {
  const { allDay = false, locale = LocaleEnum.LT } = options;

  const startDate = new Date(start);
  const endDate = end ? new Date(end) : null;

  const dateOnly: Intl.DateTimeFormatOptions = { dateStyle: 'long' };
  // Events happen in Lithuania, so times stay 24-hour even on the English site, where
  // `en` would otherwise render "06:00 PM".
  const timeOnly: Intl.DateTimeFormatOptions = { hour: '2-digit', minute: '2-digit', hourCycle: 'h23' };

  // No end date, or an end that adds nothing — a single point in time.
  if (!endDate || endDate.getTime() <= startDate.getTime()) {
    return {
      primary: formatStaticTime(startDate, dateOnly, locale),
      secondary: allDay ? allDayLabel(locale) : formatStaticTime(startDate, timeOnly, locale),
    };
  }

  if (isSameDay(startDate, endDate)) {
    return {
      primary: formatStaticTime(startDate, dateOnly, locale),
      secondary: allDay
        ? allDayLabel(locale)
        : `${formatStaticTime(startDate, timeOnly, locale)}–${formatStaticTime(endDate, timeOnly, locale)}`,
    };
  }

  return {
    primary: formatRangeOrFallback(startDate, endDate, dateOnly, locale),
    secondary: allDay
      ? formatDayCount(startDate, endDate, locale)
      : `${formatStaticTime(startDate, timeOnly, locale)} → ${formatStaticTime(endDate, timeOnly, locale)}`,
  };
};

const allDayLabel = (locale: LocaleEnum): string =>
  locale === LocaleEnum.LT ? 'Visą dieną' : 'All day';

const formatRangeOrFallback = (
  startDate: Date,
  endDate: Date,
  dateTimeOptions: Intl.DateTimeFormatOptions,
  locale: LocaleEnum,
): string => {
  const formatter = new Intl.DateTimeFormat(locale, dateTimeOptions);

  if (typeof formatter.formatRange === 'function') {
    return formatter.formatRange(startDate, endDate);
  }

  return formatDateRange(startDate, endDate, locale);
};

/**
 * Inclusive day count of a multi-day span, pluralised through `Intl.PluralRules`
 * so Lithuanian's few/many/other forms come out right.
 */
const formatDayCount = (startDate: Date, endDate: Date, locale: LocaleEnum): string => {
  const startOfDay = (date: Date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());
  const days = Math.round(
    (startOfDay(endDate).getTime() - startOfDay(startDate).getTime()) / DAY_MILISECONDS,
  ) + 1;

  if (locale !== LocaleEnum.LT) {
    return `${days} ${days === 1 ? 'day' : 'days'}`;
  }

  const forms: Record<string, string> = {
    one: 'diena',
    few: 'dienos',
    many: 'dienos',
    other: 'dienų',
  };

  const plural = new Intl.PluralRules(locale).select(days);

  return `${days} ${forms[plural] ?? forms.other}`;
};

/** The Lithuanian academic year starts in September (month index 8). */
const ACADEMIC_YEAR_START_MONTH = 8;

/**
 * Return the starting calendar year of the academic year a date falls in.
 * E.g. 2025-10-01 → 2025, 2025-03-01 → 2024 (the 2024/25 academic year).
 */
export const academicYear = (time: number | Date): number => {
  const date = new Date(time);
  return date.getMonth() >= ACADEMIC_YEAR_START_MONTH
    ? date.getFullYear()
    : date.getFullYear() - 1;
};

/** The starting year of the academic year that contains today. */
export const currentAcademicYear = (): number => academicYear(new Date());

/**
 * Format an academic year from its starting year as a compact label, e.g.
 * 2025 → "2025/26".
 */
export const formatAcademicYearLabel = (startYear: number): string => {
  const endYear = (startYear + 1) % 100;
  return `${startYear}/${endYear.toString().padStart(2, '0')}`;
};
