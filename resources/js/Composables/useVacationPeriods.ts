import { ref } from 'vue';

/**
 * Academic vacation periods, served by the backend
 * (`App\Services\AcademicCalendarService`), which is also the source used when
 * deciding whether an institution has gone too long without a meeting.
 *
 * Periods are cached per year for the lifetime of the page, and shared across
 * every component that uses this composable, so the Gantt chart fetches each
 * year at most once no matter how far the user scrolls.
 */

export type VacationType = 'summer' | 'winter' | 'easter';

export interface VacationPeriod {
  start: Date;
  end: Date;
  type: VacationType;
}

interface VacationPeriodPayload {
  start: string;
  end: string;
  type: VacationType;
}

/** Periods loaded so far, keyed by the year they start in. */
const periodsByYear = new Map<number, VacationPeriod[]>();

/** In-flight requests, keyed by `${fromYear}-${toYear}`, so parallel callers share one fetch. */
const pendingRequests = new Map<string, Promise<void>>();

/** All loaded periods, flattened. Reactive so charts re-render once data arrives. */
const periods = ref<VacationPeriod[]>([]);

function flattenLoadedPeriods(): void {
  periods.value = [...periodsByYear.values()].flat();
}

async function fetchYears(fromYear: number, toYear: number): Promise<void> {
  const url = route('api.v1.admin.academicCalendar.vacations', {
    from_year: fromYear,
    to_year: toYear,
  });

  const response = await fetch(url, {
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
  });

  if (!response.ok) {
    throw new Error(`Failed to load vacation periods (${response.status})`);
  }

  const body: { success: boolean; data?: VacationPeriodPayload[] } = await response.json();

  if (!body.success || !body.data) {
    throw new Error('Failed to load vacation periods');
  }

  // Seed every requested year, so a year with no periods is not fetched again.
  for (let year = fromYear; year <= toYear; year++) {
    periodsByYear.set(year, []);
  }

  for (const payload of body.data) {
    const period: VacationPeriod = {
      start: new Date(`${payload.start}T00:00:00`),
      end: new Date(`${payload.end}T00:00:00`),
      type: payload.type,
    };

    periodsByYear.get(period.start.getFullYear())?.push(period);
  }

  flattenLoadedPeriods();
}

export function useVacationPeriods() {
  /**
   * Load any years in [fromYear, toYear] that are not cached yet.
   *
   * Fails silently — the chart renders without vacation shading rather than
   * breaking, and the next range change retries.
   */
  async function ensureYears(fromYear: number, toYear: number): Promise<void> {
    const missing: number[] = [];

    for (let year = fromYear; year <= toYear; year++) {
      if (!periodsByYear.has(year)) {
        missing.push(year);
      }
    }

    if (missing.length === 0) {
      return;
    }

    // Request one contiguous span rather than a call per year.
    const first = Math.min(...missing);
    const last = Math.max(...missing);
    const key = `${first}-${last}`;

    const request = pendingRequests.get(key) ?? fetchYears(first, last)
      .catch(() => {
        // Allow a later range change to retry.
        for (const year of missing) {
          periodsByYear.delete(year);
        }
      })
      .finally(() => pendingRequests.delete(key));

    pendingRequests.set(key, request);

    return request;
  }

  /**
   * Periods overlapping the given range. A period counts as overlapping when any
   * of its days fall inside the range.
   */
  function periodsInRange(from: Date, to: Date): VacationPeriod[] {
    return periods.value.filter(period => period.start <= to && period.end >= from);
  }

  return { periods, ensureYears, periodsInRange };
}
