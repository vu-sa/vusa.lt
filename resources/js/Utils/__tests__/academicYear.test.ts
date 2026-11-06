import { describe, expect, it } from 'vitest';

import { academicYear, formatAcademicYearLabel } from '../academicYear';

describe('academicYear', () => {
  it('starts the academic year in September', () => {
    expect(academicYear('2025-10-01')).toBe(2025);
    expect(academicYear('2025-03-01')).toBe(2024);
  });

  it('turns over on 1 September in Vilnius, not UTC', () => {
    expect(academicYear('2025-08-31T21:30:00Z')).toBe(2025);
  });

  it('labels the year with its two-digit end', () => {
    expect(formatAcademicYearLabel(2025)).toBe('2025/26');
    expect(formatAcademicYearLabel(2099)).toBe('2099/00');
  });
});
