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

export interface ZonedParts {
  year: number;
  /** 1–12 */
  month: number;
  day: number;
  hour: number;
  minute: number;
  second: number;
}

const partsFormatters = new Map<string, Intl.DateTimeFormat>();

/** A moment's wall-clock fields in a time zone (Europe/Vilnius by default). */
export function getZonedParts(date: Date, timeZone: string = VILNIUS_TIMEZONE): ZonedParts {
  let formatter = partsFormatters.get(timeZone);
  if (!formatter) {
    formatter = new Intl.DateTimeFormat('en-US', {
      timeZone,
      hourCycle: 'h23',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
    partsFormatters.set(timeZone, formatter);
  }

  const parts: Record<string, number> = {};
  for (const part of formatter.formatToParts(date)) {
    if (part.type !== 'literal') {
      parts[part.type] = Number(part.value);
    }
  }

  return {
    year: parts.year ?? 0,
    month: parts.month ?? 0,
    day: parts.day ?? 0,
    hour: parts.hour ?? 0,
    minute: parts.minute ?? 0,
    second: parts.second ?? 0,
  };
}

function zoneOffsetMs(date: Date, timeZone: string): number {
  const p = getZonedParts(date, timeZone);
  return Date.UTC(p.year, p.month - 1, p.day, p.hour, p.minute, p.second)
    - (date.getTime() - date.getUTCMilliseconds());
}

// `Y-m-d`, `Y-m-d H:i` or `Y-m-d H:i:s(.u)` with no zone designator — how the backend
// serialises its Europe/Vilnius wall-clock casts.
const NAIVE_DATE_TIME = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2})(?:\.(\d{1,6}))?)?)?$/;

function parseNaiveInZone(match: RegExpExecArray, timeZone: string): Date {
  const [, year, month, day, hour = '0', minute = '0', second = '0', fraction = '0'] = match;
  const wallClockAsUtc = Date.UTC(
    Number(year), Number(month) - 1, Number(day),
    Number(hour), Number(minute), Number(second), Number(fraction.slice(0, 3).padEnd(3, '0')),
  );

  // Re-check the offset at the resulting instant so a DST switch between the two is honoured.
  const firstGuess = wallClockAsUtc - zoneOffsetMs(new Date(wallClockAsUtc), timeZone);
  return new Date(wallClockAsUtc - zoneOffsetMs(new Date(firstGuess), timeZone));
}

/**
 * Parse a date input into a valid Date, or null. A string without a zone designator is
 * Europe/Vilnius wall-clock time, not the browser's (or the SSR server's) local time.
 */
export function parseDate(input: DateInput): Date | null {
  if (input === null || input === undefined || input === '') {
    return null;
  }

  if (typeof input === 'string') {
    const naive = NAIVE_DATE_TIME.exec(input.trim());
    if (naive) {
      return parseNaiveInZone(naive, VILNIUS_TIMEZONE);
    }
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

/** A record date from either collection source: search documents hold Unix seconds, trash rows ISO strings. */
export function formatRecordDate(value: number | string | null | undefined): string {
  return formatDate(typeof value === 'number' ? value * 1000 : value) || '—';
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

export interface ZoneLocaleOptions {
  locale?: string;
  timeZone?: string;
}

/**
 * Format with explicit Intl fields, still in Europe/Vilnius — for the shapes the named
 * formatters don't cover (a bare day, "Sep 2026", "Sat, Sep 19"). Hours are always 24-hour.
 */
export function formatDateWith(
  input: DateInput,
  fields: Intl.DateTimeFormatOptions,
  options: ZoneLocaleOptions = {},
): string {
  const date = parseDate(input);
  if (!date) return '';

  const { locale = DEFAULT_LOCALE, timeZone = VILNIUS_TIMEZONE } = options;
  const hourCycle = fields.hour !== undefined || fields.timeStyle !== undefined ? 'h23' : undefined;

  return new Intl.DateTimeFormat(locale, { ...fields, hourCycle, timeZone }).format(date);
}

/** Whether two moments fall on the same calendar day in Europe/Vilnius. */
export function isSameDay(a: DateInput, b: DateInput, timeZone: string = VILNIUS_TIMEZONE): boolean {
  const first = formatDate(a, { timeZone });
  return first !== '' && first === formatDate(b, { timeZone });
}

/**
 * Conventional Lithuanian three-letter month abbreviations, indexed by month number (0-11).
 * Several are contractions (rugpjūtis → RGP, rugsėjis → RGS, gruodis → GRD), and Intl can't
 * help: Lithuanian has no abbreviated month names in CLDR, so `{ month: 'short' }` gives "09".
 */
const LT_MONTH_ABBREVIATIONS = [
  'SAU', 'VAS', 'KOV', 'BAL', 'GEG', 'BIR',
  'LIE', 'RGP', 'RGS', 'SPA', 'LAP', 'GRD',
] as const;

/** A three-letter month abbreviation for compact date plates — "RGS", "SEP". */
export function formatMonthAbbr(input: DateInput, options: ZoneLocaleOptions = {}): string {
  const date = parseDate(input);
  if (!date) return '';

  const { locale = DEFAULT_LOCALE, timeZone = VILNIUS_TIMEZONE } = options;

  if (locale.startsWith('lt')) {
    return LT_MONTH_ABBREVIATIONS[getZonedParts(date, timeZone).month - 1] ?? '';
  }

  return new Intl.DateTimeFormat(locale, { month: 'short', timeZone })
    .format(date)
    .replace(/\.$/, '')
    .slice(0, 3)
    .toUpperCase();
}

export interface EventDateSpan {
  /** The date part, e.g. "2026 m. rugpjūčio 25–27 d." */
  primary: string;
  /** The time part or duration hint, e.g. "10:00–18:00" or "3 dienos". */
  secondary: string;
}

/**
 * An event's start/end as a fluent, locale-correct span. Multi-day ranges go through
 * `Intl.DateTimeFormat.formatRange`, which collapses shared parts the way each locale expects
 * ("2026 m. rugpjūčio 25–27 d.") instead of printing two full dates.
 */
export function formatEventDateSpan(
  start: DateInput,
  end?: DateInput,
  options: ZoneLocaleOptions & { allDay?: boolean } = {},
): EventDateSpan {
  const { allDay = false, locale = DEFAULT_LOCALE, timeZone = VILNIUS_TIMEZONE } = options;

  const startDate = parseDate(start);
  if (!startDate) {
    return { primary: '', secondary: '' };
  }

  const endDate = parseDate(end);
  const zone = { locale, timeZone };
  const date = (value: Date) => formatDate(value, { ...zone, format: 'full' });
  const time = (value: Date) => formatTime(value, zone);
  const allDayLabel = locale.startsWith('lt') ? 'Visą dieną' : 'All day';

  // No end, or an end that adds nothing — a single point in time.
  if (!endDate || endDate.getTime() <= startDate.getTime()) {
    return { primary: date(startDate), secondary: allDay ? allDayLabel : time(startDate) };
  }

  if (isSameDay(startDate, endDate, timeZone)) {
    return {
      primary: date(startDate),
      secondary: allDay ? allDayLabel : `${time(startDate)}–${time(endDate)}`,
    };
  }

  const rangeFormatter = new Intl.DateTimeFormat(locale, { dateStyle: 'long', timeZone });

  return {
    primary: rangeFormatter.formatRange(startDate, endDate),
    secondary: allDay
      ? formatDayCount(startDate, endDate, locale, timeZone)
      : `${time(startDate)} → ${time(endDate)}`,
  };
}

/** Inclusive day count of a multi-day span, with Lithuanian's plural forms. */
function formatDayCount(startDate: Date, endDate: Date, locale: string, timeZone: string): string {
  const calendarDay = (value: Date) => {
    const p = getZonedParts(value, timeZone);
    return Date.UTC(p.year, p.month - 1, p.day);
  };
  const days = Math.round((calendarDay(endDate) - calendarDay(startDate)) / DAY_MS) + 1;

  if (!locale.startsWith('lt')) {
    return `${days} ${days === 1 ? 'day' : 'days'}`;
  }

  const forms: Record<string, string> = { one: 'diena', few: 'dienos', many: 'dienos', other: 'dienų' };

  return `${days} ${forms[new Intl.PluralRules('lt').select(days)] ?? forms.other}`;
}

/**
 * A picker's Date as the `Y-m-d H:i:s` wall-clock string the backend reads as Europe/Vilnius.
 * Uses the browser's local fields on purpose: that is the time the user picked.
 */
export function toNaiveDateTimeString(date: Date): string {
  const pad = (value: number) => String(value).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} `
    + `${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}
