import { describe, expect, it } from 'vitest';

import {
  formatDate,
  formatDateFact,
  formatDateTime,
  formatNearDate,
  formatTime,
  parseDate,
} from '../dateTime';

describe('dateTime utilities (U9)', () => {
  describe('parseDate', () => {
    it('parses ISO strings, timestamps, and Date objects', () => {
      expect(parseDate('2026-09-19T10:00:00Z')).toBeInstanceOf(Date);
      expect(parseDate(1774000000000)).toBeInstanceOf(Date);
      expect(parseDate(new Date())).toBeInstanceOf(Date);
    });

    it('returns null for empty, null, undefined, or invalid inputs', () => {
      expect(parseDate(null)).toBeNull();
      expect(parseDate(undefined)).toBeNull();
      expect(parseDate('')).toBeNull();
      expect(parseDate('invalid-date')).toBeNull();
    });
  });

  describe('formatDate', () => {
    // 2026-09-18T22:30:00Z is 2026-09-19 01:30 in Vilnius (UTC+3)
    const utcMidnightCross = '2026-09-18T22:30:00Z';

    it('formats absolute date as YYYY-MM-DD by default in Europe/Vilnius', () => {
      expect(formatDate(utcMidnightCross)).toBe('2026-09-19');
    });

    it('formats full long date in Lithuanian', () => {
      const formatted = formatDate(utcMidnightCross, { format: 'full', locale: 'lt' });
      expect(formatted).toContain('2026');
      expect(formatted).toContain('rugsėjo');
      expect(formatted).toContain('19');
    });

    it('formats full long date in English', () => {
      const formatted = formatDate(utcMidnightCross, { format: 'full', locale: 'en' });
      expect(formatted).toContain('September');
      expect(formatted).toContain('19');
      expect(formatted).toContain('2026');
    });

    it('formats short month + day', () => {
      const formatted = formatDate(utcMidnightCross, { format: 'short', locale: 'lt' });
      expect(formatted).toContain('rugsėjo');
      expect(formatted).toContain('19');
    });

    it('returns empty string for null or invalid inputs', () => {
      expect(formatDate(null)).toBe('');
      expect(formatDate(undefined)).toBe('');
      expect(formatDate('not-a-date')).toBe('');
    });
  });

  describe('formatTime', () => {
    it('formats 24-hour time in Europe/Vilnius', () => {
      // 10:30 UTC -> 13:30 Vilnius (EEST, UTC+3)
      const date = '2026-09-19T10:30:00Z';
      expect(formatTime(date)).toBe('13:30');
    });

    it('handles seconds when includeSeconds is true', () => {
      const date = '2026-09-19T10:30:45Z';
      expect(formatTime(date, { includeSeconds: true })).toBe('13:30:45');
    });

    it('returns empty string for null or invalid inputs', () => {
      expect(formatTime(null)).toBe('');
    });
  });

  describe('formatDateTime', () => {
    it('combines date and time in Europe/Vilnius', () => {
      const date = '2026-09-19T10:30:00Z';
      expect(formatDateTime(date)).toBe('2026-09-19 13:30');
    });
  });

  describe('formatNearDate', () => {
    const now = new Date('2026-09-19T12:00:00Z');

    it('formats relative near dates in Lithuanian (prieš 2 val., po 3 d.)', () => {
      // < 1 min
      expect(formatNearDate(new Date('2026-09-19T11:59:45Z'), { now })).toBe('ką tik');

      // Minutes
      expect(formatNearDate(new Date('2026-09-19T11:55:00Z'), { now })).toBe('prieš 5 min.');
      expect(formatNearDate(new Date('2026-09-19T12:05:00Z'), { now })).toBe('po 5 min.');

      // Hours
      expect(formatNearDate(new Date('2026-09-19T10:00:00Z'), { now })).toBe('prieš 2 val.');
      expect(formatNearDate(new Date('2026-09-19T14:00:00Z'), { now })).toBe('po 2 val.');

      // Days
      expect(formatNearDate(new Date('2026-09-18T12:00:00Z'), { now })).toBe('vakar');
      expect(formatNearDate(new Date('2026-09-20T12:00:00Z'), { now })).toBe('rytoj');
      expect(formatNearDate(new Date('2026-09-16T12:00:00Z'), { now })).toBe('prieš 3 d.');
      expect(formatNearDate(new Date('2026-09-22T12:00:00Z'), { now })).toBe('po 3 d.');
    });

    it('formats relative near dates in English', () => {
      expect(formatNearDate(new Date('2026-09-19T11:59:45Z'), { now, locale: 'en' })).toBe('just now');
      expect(formatNearDate(new Date('2026-09-19T11:55:00Z'), { now, locale: 'en' })).toBe('5m ago');
      expect(formatNearDate(new Date('2026-09-19T10:00:00Z'), { now, locale: 'en' })).toBe('2h ago');
      expect(formatNearDate(new Date('2026-09-18T12:00:00Z'), { now, locale: 'en' })).toBe('yesterday');
      expect(formatNearDate(new Date('2026-09-16T12:00:00Z'), { now, locale: 'en' })).toBe('3d ago');
    });

    it('falls back to absolute date when beyond thresholdDays', () => {
      // 10 days ago (threshold 7)
      const tenDaysAgo = new Date('2026-09-09T12:00:00Z');
      expect(formatNearDate(tenDaysAgo, { now, thresholdDays: 7 })).toBe('2026-09-09');
    });
  });

  describe('formatDateFact', () => {
    const now = new Date('2026-09-19T12:00:00Z');

    it('returns relative display and absolute tooltip when near', () => {
      const nearDate = new Date('2026-09-19T10:00:00Z');
      const fact = formatDateFact(nearDate, { now, thresholdDays: 7 });

      expect(fact.isNear).toBe(true);
      expect(fact.display).toBe('prieš 2 val.');
      expect(fact.tooltip).toBe('2026-09-19 13:00');
      expect(fact.relative).toBe('prieš 2 val.');
      expect(fact.absolute).toBe('2026-09-19 13:00');
    });

    it('returns absolute display and relative tooltip when far', () => {
      const farDate = new Date('2026-08-01T10:00:00Z');
      const fact = formatDateFact(farDate, { now, thresholdDays: 7 });

      expect(fact.isNear).toBe(false);
      expect(fact.display).toBe('2026-08-01');
      expect(fact.absolute).toBe('2026-08-01 13:00');
    });

    it('handles null date gracefully', () => {
      const fact = formatDateFact(null);
      expect(fact.display).toBe('');
      expect(fact.isNear).toBe(false);
    });
  });
});
