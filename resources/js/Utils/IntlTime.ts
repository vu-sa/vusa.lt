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
