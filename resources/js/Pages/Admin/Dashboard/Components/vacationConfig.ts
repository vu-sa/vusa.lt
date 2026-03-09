/**
 * Vacation and Holiday Configuration for Lithuania Academic Calendar
 * 
 * This module contains the logic for calculating academic vacation periods
 * used in the Timeline Gantt Chart visualization.
 */

export interface VacationPeriod {
  start: Date;
  end: Date;
  type: 'summer' | 'winter' | 'easter';
}

/**
 * Calculate Easter Sunday for a given year using the Computus algorithm
 * (Anonymous Gregorian algorithm)
 * 
 * @param year - The year for which to calculate Easter
 * @returns Date object representing Easter Sunday
 * 
 * @see https://en.wikipedia.org/wiki/Computus
 */
export const getEasterSunday = (year: number): Date => {
  const a = year % 19;
  const b = Math.floor(year / 100);
  const c = year % 100;
  const d = Math.floor(b / 4);
  const e = b % 4;
  const f = Math.floor((b + 8) / 25);
  const g = Math.floor((b - f + 1) / 3);
  const h = (19 * a + b - d - g + 15) % 30;
  const i = Math.floor(c / 4);
  const k = c % 4;
  const l = (32 + 2 * e + 2 * i - h - k) % 7;
  const m = Math.floor((a + 11 * h + 22 * l) / 451);
  const month = Math.floor((h + l - 7 * m + 114) / 31);
  const day = ((h + l - 7 * m + 114) % 31) + 1;
  return new Date(year, month - 1, day);
};

/**
 * Get academic vacation periods within a date range
 * 
 * Calculates all academic vacation periods for Lithuanian universities
 * that fall within the specified date range.
 * 
 * Vacation periods include:
 * - Summer vacation: July 1 - August 31
 * - Winter vacation: December 24 - January 1
 * - Late January vacation: Last Monday of January - February 4
 * - Easter vacation: Week before Easter - Easter Monday + 1 day
 * 
 * @param minDate - Start of the date range
 * @param maxDate - End of the date range
 * @returns Array of vacation periods that overlap with the given range
 */
export const getVacationPeriods = (minDate: Date, maxDate: Date): VacationPeriod[] => {
  const periods: VacationPeriod[] = [];
  
  // Get the years covered by our timeline
  const startYear = minDate.getFullYear();
  const endYear = maxDate.getFullYear();
  
  for (let year = startYear; year <= endYear; year++) {
    // Summer vacation: July 1 - August 31
    const summerStart = new Date(year, 6, 1); // July 1
    const summerEnd = new Date(year, 8, 0); // August 31 (last day of August)
    periods.push({ start: summerStart, end: summerEnd, type: 'summer' });
    
    // Winter vacation: December 24 - January 1 (next year)
    const winterStart = new Date(year, 11, 24); // December 24
    const winterEnd = new Date(year + 1, 0, 1); // January 1 (next year)
    periods.push({ start: winterStart, end: winterEnd, type: 'winter' });
    
    // Late January vacation: last Monday of January until February 4
    // Find last Monday of January
    const lastDayOfJan = new Date(year, 1, 0); // Last day of January
    const lastMondayOfJan = new Date(lastDayOfJan);
    lastMondayOfJan.setDate(lastDayOfJan.getDate() - ((lastDayOfJan.getDay() + 6) % 7));
    const lateJanEnd = new Date(year, 1, 4); // February 4
    periods.push({ start: lastMondayOfJan, end: lateJanEnd, type: 'winter' });
    
    // Easter vacation: week before Easter + Easter days
    const easter = getEasterSunday(year);
    const easterStart = new Date(easter);
    easterStart.setDate(easter.getDate() - 7); // Week before Easter
    const easterEnd = new Date(easter);
    easterEnd.setDate(easter.getDate() + 2); // Easter + 2 days (Easter Monday + 1)
    periods.push({ start: easterStart, end: easterEnd, type: 'easter' });
  }
  
  // Filter periods to only those that overlap with our timeline
  return periods.filter(period => 
    period.start <= maxDate && period.end >= minDate
  );
};

/**
 * Check if a given date falls within any vacation period
 * 
 * @param date - Date to check
 * @param vacationPeriods - Array of vacation periods to check against
 * @returns true if the date falls within a vacation period
 */
export const isVacationDate = (date: Date, vacationPeriods: VacationPeriod[]): boolean => {
  return vacationPeriods.some(period => 
    date >= period.start && date <= period.end
  );
};

/**
 * Get the vacation type for a given date
 * 
 * @param date - Date to check
 * @param vacationPeriods - Array of vacation periods to check against
 * @returns Vacation type or null if not a vacation date
 */
export const getVacationType = (date: Date, vacationPeriods: VacationPeriod[]): 'summer' | 'winter' | 'easter' | null => {
  const period = vacationPeriods.find(p => 
    date >= p.start && date <= p.end
  );
  return period?.type ?? null;
};
