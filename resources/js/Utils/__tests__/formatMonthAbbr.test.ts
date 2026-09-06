import { describe, expect, it } from 'vitest';

import { formatMonthAbbr } from '../IntlTime';
import { LocaleEnum } from '@/Types/enums';

describe('formatMonthAbbr', () => {
  /**
   * The reason this helper exists at all: Lithuanian has no abbreviated month names in CLDR, so
   * Intl's `{ month: 'short' }` returns a bare number — which is why the older
   * `formatMonthShort()` renders "09" on a date badge instead of a month.
   */
  it('uses the conventional Lithuanian abbreviations', () => {
    expect(formatMonthAbbr(new Date(2026, 8, 4))).toBe('RGS');
    expect(formatMonthAbbr(new Date(2026, 7, 12))).toBe('RGP');
    expect(formatMonthAbbr(new Date(2026, 0, 1))).toBe('SAU');
    expect(formatMonthAbbr(new Date(2026, 11, 24))).toBe('GRD');
  });

  /**
   * RGP/RGS and GRD are contractions, not truncations — truncating "rugpjūtis" and "rugsėjis"
   * both give "RUG", which is exactly the collision the convention exists to avoid.
   */
  it('keeps August and September distinct', () => {
    expect(formatMonthAbbr(new Date(2026, 7, 1)))
      .not.toBe(formatMonthAbbr(new Date(2026, 8, 1)));
  });

  it('falls back to Intl for other locales', () => {
    expect(formatMonthAbbr(new Date(2026, 8, 4), LocaleEnum.EN)).toBe('SEP');
  });

  it('returns an empty string for a missing date', () => {
    expect(formatMonthAbbr(undefined)).toBe('');
  });
});
