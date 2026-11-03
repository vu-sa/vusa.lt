import { getZonedParts, parseDate, type DateInput } from '@/Utils/dateTime';

/** The Lithuanian academic year starts in September. */
const ACADEMIC_YEAR_START_MONTH = 9;

/**
 * The starting calendar year of the academic year a date falls in (Europe/Vilnius):
 * 2025-10-01 → 2025, 2025-03-01 → 2024 (the 2024/25 academic year).
 */
export function academicYear(input: DateInput): number {
  const { year, month } = getZonedParts(parseDate(input) ?? new Date());
  return month >= ACADEMIC_YEAR_START_MONTH ? year : year - 1;
}

/** The starting year of the academic year that contains today. */
export function currentAcademicYear(): number {
  return academicYear(new Date());
}

/** A compact label from the starting year: 2025 → "2025/26". */
export function formatAcademicYearLabel(startYear: number): string {
  return `${startYear}/${String((startYear + 1) % 100).padStart(2, '0')}`;
}
