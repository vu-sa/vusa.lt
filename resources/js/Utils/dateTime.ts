/**
 * Canonical Date and Time Formatter (U9)
 *
 * Locked to `Europe/Vilnius` timezone and Lithuanian formats by default.
 * Provides consistent formatting across tables (absolute), record facts (relative when near),
 * and collections.
 */

export const VILNIUS_TIMEZONE = 'Europe/Vilnius';
export const DEFAULT_LOCALE = 'lt';

const MINUTE_MS = 60 * 1000;
const HOUR_MS = 60 * MINUTE_MS;
const DAY_MS = 24 * HOUR_MS;

export type DateInput = string | number | Date | null | undefined;

export interface FormatDateOptions {
  /**
   * 'iso': YYYY-MM-DD (standard tabular Lithuanian format, e.g. "2026-09-19")
   * 'full': Long localized date (e.g. "2026 m. rugsėjo 19 d.")
   * 'short': Short localized date (e.g. "rugsėjo 19 d.")
   */
  format?: 'iso' | 'full' | 'short';
  locale?: string;
  timeZone?: string;
}

export interface FormatTimeOptions {
  locale?: string;
  timeZone?: string;
  includeSeconds?: boolean;
}

export interface FormatDateTimeOptions {
  format?: 'iso' | 'full';
  locale?: string;
  timeZone?: string;
}

export interface FormatNearOptions {
  /** Number of days within which relative formatting applies. Defaults to 7. */
  thresholdDays?: number;
  /** Relative reference time (defaults to new Date()). Useful for testing. */
  now?: Date;
  locale?: string;
  timeZone?: string;
  /** Format to use when date is beyond threshold. Defaults to 'iso'. */
  fallbackFormat?: 'iso' | 'full';
}

export interface DateFact {
  /** Main string to display (relative when near, absolute when far) */
  display: string;
  /** Complementary string for tooltip (absolute when near, relative when far) */
  tooltip: string;
  /** Whether the date is within the near threshold */
  isNear: boolean;
  /** Relative representation */
  relative: string;
  /** Absolute representation */
  absolute: string;
}

/**
 * Safely parse a date input into a valid Date object, or null if invalid.
 */
export function parseDate(input: DateInput): Date | null {
  if (input === null || input === undefined || input === '') {
    return null;
  }

  const date = input instanceof Date ? input : new Date(input);
  if (Number.isNaN(date.getTime())) {
    return null;
  }

  return date;
}

/**
 * Format an absolute date in Europe/Vilnius.
 * Default format is 'iso' (YYYY-MM-DD), matching table requirements.
 */
export function formatDate(input: DateInput, options: FormatDateOptions = {}): string {
  const date = parseDate(input);
  if (!date) return '';

  const {
    format = 'iso',
    locale = DEFAULT_LOCALE,
    timeZone = VILNIUS_TIMEZONE,
  } = options;

  if (format === 'iso') {
    // In Lithuanian locale, lt-LT formats date as YYYY-MM-DD
    const formatter = new Intl.DateTimeFormat('lt-LT', {
      timeZone,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    });
    return formatter.format(date);
  }

  if (format === 'full') {
    const formatter = new Intl.DateTimeFormat(locale, {
      timeZone,
      dateStyle: 'long',
    });
    return formatter.format(date);
  }

  // 'short'
  const formatter = new Intl.DateTimeFormat(locale, {
    timeZone,
    month: 'long',
    day: 'numeric',
  });
  return formatter.format(date);
}

/**
 * Today as YYYY-MM-DD in Europe/Vilnius. `toISOString().split('T')[0]` is the UTC date and
 * reads as yesterday between 00:00 and 03:00 in Lithuania.
 */
export function todayIso(now: Date = new Date()): string {
  return formatDate(now, { format: 'iso' });
}

/**
 * Format a 24-hour time in Europe/Vilnius (e.g. "14:30").
 */
export function formatTime(input: DateInput, options: FormatTimeOptions = {}): string {
  const date = parseDate(input);
  if (!date) return '';

  const {
    locale = DEFAULT_LOCALE,
    timeZone = VILNIUS_TIMEZONE,
    includeSeconds = false,
  } = options;

  const formatter = new Intl.DateTimeFormat(locale, {
    timeZone,
    hour: '2-digit',
    minute: '2-digit',
    second: includeSeconds ? '2-digit' : undefined,
    hourCycle: 'h23',
  });

  return formatter.format(date);
}

/**
 * Format date and time in Europe/Vilnius (e.g. "2026-09-19 14:30").
 */
export function formatDateTime(input: DateInput, options: FormatDateTimeOptions = {}): string {
  const date = parseDate(input);
  if (!date) return '';

  const {
    format = 'iso',
    locale = DEFAULT_LOCALE,
    timeZone = VILNIUS_TIMEZONE,
  } = options;

  const dateStr = formatDate(date, { format, locale, timeZone });
  const timeStr = formatTime(date, { locale, timeZone });

  return `${dateStr} ${timeStr}`;
}

/**
 * Format a date relatively when near ("prieš 2 val.", "po 3 d."),
 * falling back to absolute date when beyond threshold (U9).
 */
export function formatNearDate(input: DateInput, options: FormatNearOptions = {}): string {
  const date = parseDate(input);
  if (!date) return '';

  const {
    thresholdDays = 7,
    now = new Date(),
    locale = DEFAULT_LOCALE,
    timeZone = VILNIUS_TIMEZONE,
    fallbackFormat = 'iso',
  } = options;

  const diffMs = date.getTime() - now.getTime();
  const absMs = Math.abs(diffMs);
  const isPast = diffMs < 0;
  const isLt = locale.startsWith('lt');

  // Under 1 minute
  if (absMs < MINUTE_MS) {
    return isLt ? 'ką tik' : 'just now';
  }

  // Under 1 hour -> minutes
  if (absMs < HOUR_MS) {
    const mins = Math.round(absMs / MINUTE_MS);
    if (isLt) {
      return isPast ? `prieš ${mins} min.` : `po ${mins} min.`;
    }
    return isPast ? `${mins}m ago` : `in ${mins}m`;
  }

  // Under 24 hours -> hours
  if (absMs < DAY_MS) {
    const hours = Math.round(absMs / HOUR_MS);
    if (isLt) {
      return isPast ? `prieš ${hours} val.` : `po ${hours} val.`;
    }
    return isPast ? `${hours}h ago` : `in ${hours}h`;
  }

  // Within day threshold
  const days = Math.round(absMs / DAY_MS);

  if (days <= thresholdDays) {
    if (days === 1) {
      if (isLt) return isPast ? 'vakar' : 'rytoj';
      return isPast ? 'yesterday' : 'tomorrow';
    }

    if (isLt) {
      return isPast ? `prieš ${days} d.` : `po ${days} d.`;
    }
    return isPast ? `${days}d ago` : `in ${days}d`;
  }

  // Beyond threshold -> fallback to absolute date
  return formatDate(date, { format: fallbackFormat, locale, timeZone });
}

/**
 * Helper for record facts strips: relative when near with absolute date in tooltip,
 * or absolute when far with relative date in tooltip (U9).
 */
export function formatDateFact(input: DateInput, options: FormatNearOptions = {}): DateFact {
  const date = parseDate(input);
  if (!date) {
    return { display: '', tooltip: '', isNear: false, relative: '', absolute: '' };
  }

  const {
    thresholdDays = 7,
    now = new Date(),
    locale = DEFAULT_LOCALE,
    timeZone = VILNIUS_TIMEZONE,
  } = options;

  const diffMs = Math.abs(date.getTime() - now.getTime());
  const isNear = diffMs <= thresholdDays * DAY_MS;

  const relative = formatNearDate(date, { ...options, thresholdDays: 9999 });
  const absolute = formatDateTime(date, { locale, timeZone });

  return {
    display: isNear ? relative : formatDate(date, { format: 'iso', locale, timeZone }),
    tooltip: isNear ? absolute : relative,
    isNear,
    relative,
    absolute,
  };
}
