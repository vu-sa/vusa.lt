import { describe, expect, it } from 'vitest';

import { formatMonthAbbr } from '../dateTime';

describe('formatMonthAbbr', () => {
  /**
   * Lithuanian has no abbreviated month names in CLDR, so Intl's `{ month: 'short' }` returns
   * "09" — a date plate would read as two numbers stacked.
   */
  it('uses the conventional Lithuanian abbreviations', () => {
    expect(formatMonthAbbr('2026-09-04')).toBe('RGS');
    expect(formatMonthAbbr('2026-08-12')).toBe('RGP');
    expect(formatMonthAbbr('2026-01-01')).toBe('SAU');
    expect(formatMonthAbbr('2026-12-24')).toBe('GRD');
  });

  /** RGP/RGS are contractions: truncating "rugpjūtis" and "rugsėjis" both give "RUG". */
  it('keeps August and September distinct', () => {
    expect(formatMonthAbbr('2026-08-01')).not.toBe(formatMonthAbbr('2026-09-01'));
  });

  it('takes the month from the Vilnius calendar', () => {
    // 21:30 UTC on 31 August is already 1 September in Vilnius.
    expect(formatMonthAbbr('2026-08-31T21:30:00Z')).toBe('RGS');
  });

  it('falls back to Intl for other locales', () => {
    expect(formatMonthAbbr('2026-09-04', { locale: 'en' })).toBe('SEP');
  });

  it('returns an empty string for a missing date', () => {
    expect(formatMonthAbbr(undefined)).toBe('');
  });
});
