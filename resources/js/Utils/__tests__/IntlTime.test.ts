import { describe, expect, it } from 'vitest';

import { formatEventDateSpan } from '@/Utils/IntlTime';
import { LocaleEnum } from '@/Types/enums';

describe('formatEventDateSpan', () => {
  it('renders a single timed instant as a date plus a time', () => {
    const span = formatEventDateSpan('2026-08-25T10:00:00', null, { locale: LocaleEnum.LT });

    expect(span.primary).toContain('2026');
    expect(span.primary).not.toContain('10:00');
    expect(span.secondary).toBe('10:00');
  });

  it('collapses a same-day range to one date and a time range', () => {
    const span = formatEventDateSpan('2026-08-25T10:00:00', '2026-08-25T18:00:00', {
      locale: LocaleEnum.LT,
    });

    expect(span.primary).toContain('2026');
    expect(span.secondary).toBe('10:00–18:00');
  });

  it('labels an all-day event instead of printing midnight', () => {
    const span = formatEventDateSpan('2026-08-25T00:00:00', '2026-08-25T23:59:00', {
      allDay: true,
      locale: LocaleEnum.LT,
    });

    expect(span.secondary).toBe('Visą dieną');

    const english = formatEventDateSpan('2026-08-25T00:00:00', null, {
      allDay: true,
      locale: LocaleEnum.EN,
    });

    expect(english.secondary).toBe('All day');
  });

  it('renders a multi-day range as one fluent span, not two full dates', () => {
    const span = formatEventDateSpan('2026-08-25T10:00:00', '2026-08-27T18:00:00', {
      locale: LocaleEnum.LT,
    });

    // Locale-collapsed: the year and month appear once, the two days are joined.
    expect(span.primary).toContain('25');
    expect(span.primary).toContain('27');
    expect(span.primary.match(/2026/g)?.length).toBe(1);
    expect(span.secondary).toBe('10:00 → 18:00');
  });

  it('spans months and years without losing either end', () => {
    const span = formatEventDateSpan('2026-12-30T10:00:00', '2027-01-02T18:00:00', {
      locale: LocaleEnum.LT,
    });

    expect(span.primary).toContain('2026');
    expect(span.primary).toContain('2027');
  });

  it('summarises a multi-day all-day event as an inclusive day count', () => {
    expect(
      formatEventDateSpan('2026-08-25T00:00:00', '2026-08-27T00:00:00', {
        allDay: true,
        locale: LocaleEnum.LT,
      }).secondary,
    ).toBe('3 dienos');

    expect(
      formatEventDateSpan('2026-08-25T00:00:00', '2026-09-04T00:00:00', {
        allDay: true,
        locale: LocaleEnum.LT,
      }).secondary,
    ).toBe('11 dienų');

    expect(
      formatEventDateSpan('2026-08-25T00:00:00', '2026-08-27T00:00:00', {
        allDay: true,
        locale: LocaleEnum.EN,
      }).secondary,
    ).toBe('3 days');
  });

  it('treats an end date that precedes the start as no end date at all', () => {
    const span = formatEventDateSpan('2026-08-25T10:00:00', '2026-08-24T10:00:00', {
      locale: LocaleEnum.LT,
    });

    expect(span.secondary).toBe('10:00');
  });

  it('formats in English when asked', () => {
    const span = formatEventDateSpan('2026-08-25T10:00:00', '2026-08-25T18:00:00', {
      locale: LocaleEnum.EN,
    });

    expect(span.primary).toContain('August');
    expect(span.secondary).toBe('10:00–18:00');
  });
});
