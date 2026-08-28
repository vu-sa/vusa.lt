import { afterEach, describe, expect, it, vi } from 'vitest';

import { formatDuration } from '../duration';

/**
 * The i18n mock returns the key, so these assert the *shape* — which unit was picked and
 * with what number — rather than the wording, which lives in lang/admin/{lt,en}.
 */
vi.mock('laravel-vue-i18n', () => ({
  trans: (key: string, replacements?: Record<string, string>) => `${key.split('.').pop()}:${replacements?.value}`,
}));

const at = (y: number, m: number, d: number) => new Date(y, m - 1, d, 12);

afterEach(() => {
  vi.useRealTimers();
});

describe('formatDuration', () => {
  it('reads a multi-year seat as years and months', () => {
    expect(formatDuration(at(2022, 5, 18), at(2024, 9, 30))).toBe('year:2 month:4');
  });

  it('drops the months when a term lands on a whole year', () => {
    expect(formatDuration(at(2023, 7, 1), at(2025, 7, 1))).toBe('year:2');
  });

  it('falls back to months and days under a year', () => {
    expect(formatDuration(at(2024, 7, 1), at(2024, 11, 15))).toBe('month:4 day:15');
  });

  it('falls back to days for a stand-in', () => {
    expect(formatDuration(at(2024, 7, 1), at(2024, 7, 3))).toBe('day:3');
  });

  it('counts a single-day seat as a day, not as nothing', () => {
    expect(formatDuration(at(2024, 7, 1), at(2024, 7, 1))).toBe('day:1');
  });

  it('measures an open-ended seat up to today', () => {
    vi.useFakeTimers().setSystemTime(new Date(2025, 10, 1, 12));

    expect(formatDuration(at(2023, 7, 1), null)).toBe('year:2 month:4');
  });

  it('refuses to invent a length for an inverted period', () => {
    expect(formatDuration(at(2025, 7, 1), at(2024, 7, 1))).toBe('—');
  });
});
