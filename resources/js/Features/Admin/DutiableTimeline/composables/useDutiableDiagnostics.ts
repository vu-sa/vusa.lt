import { computed, type Ref } from 'vue';

import { parseTimelineDate, toDateString } from './useDutiableTimelineData';
import type { ParsedCadence, ParsedRow, StagedDates, TimelineDiagnostic, TimelineOperation } from '../types';

/** Mirrors AnalyzeDutiableTimeline::OFF_CADENCE_MAX_DAYS. */
export const OFF_CADENCE_MAX_DAYS = 45;

/**
 * The term a date belongs to: the one containing it, else the one whose start is nearest.
 *
 * An institution that defines its own cadences never falls back to the global ladder —
 * same rule as ResolveCadenceForDuty::pick() and AnalyzeDutiableTimeline::applicable().
 */
export function resolveCadenceFor(
  cadences: ParsedCadence[],
  row: Pick<ParsedRow, 'institution_id'>,
  date: string,
): ParsedCadence | null {
  const scoped = cadences.filter(cadence => cadence.institution_id === row.institution_id);
  const pool = scoped.length > 0 ? scoped : cadences.filter(cadence => cadence.institution_id === null);
  const at = parseTimelineDate(date);

  return pool.find(cadence => at >= cadence.startDate && at <= cadence.endDate)
    ?? [...pool].sort(
      (a, b) => Math.abs(a.startDate.getTime() - at.getTime()) - Math.abs(b.startDate.getTime() - at.getTime()),
    )[0]
    ?? null;
}

/** Only the server can see this one: it needs a join over ex_officio_duties. */
const SERVER_ONLY_CODES = new Set(['orphan_derived_suspect', 'understaffed']);

const MS_PER_DAY = 86_400_000;

function daysBetween(a: string, b: string): number {
  return Math.round(Math.abs(parseTimelineDate(a).getTime() - parseTimelineDate(b).getTime()) / MS_PER_DAY);
}

function dayBefore(date: string): string {
  const value = parseTimelineDate(date);
  value.setDate(value.getDate() - 1);

  return toDateString(value);
}

/**
 * The same checks AnalyzeDutiableTimeline runs, recomputed over *staged* state so the
 * panel updates while the user drags rather than only after a save.
 *
 * The server's version stays authoritative — it ships in the payload, gates every fix,
 * and produces the preview's before/after delta. The two are kept honest by sharing
 * fixture cases with DutiableDiagnosticsTest.
 *
 * `understaffed` and `orphan_derived_suspect` are passed straight through from the
 * payload: the first depends on a duty's full holder set (not just the rows on screen)
 * and the second on a query this side cannot make.
 */
export function useDutiableDiagnostics(
  rows: Ref<ParsedRow[]>,
  cadences: Ref<ParsedCadence[]>,
  staged: Ref<Map<string, StagedDates>>,
  serverDiagnostics: Ref<TimelineDiagnostic[]>,
) {
  const periods = computed(() => {
    const map = new Map<string, { start: string; end: string | null }>();

    for (const row of rows.value) {
      const override = staged.value.get(row.id);

      map.set(row.id, {
        start: override?.start_date ?? row.start_date,
        end: override ? override.end_date : row.end_date,
      });
    }

    return map;
  });

  function applicableCadences(row: ParsedRow): ParsedCadence[] {
    const scoped = cadences.value.filter(cadence => cadence.institution_id === row.institution_id);

    return scoped.length > 0 ? scoped : cadences.value.filter(cadence => cadence.institution_id === null);
  }

  function cadenceFor(row: ParsedRow, date: string): ParsedCadence | null {
    return resolveCadenceFor(cadences.value, row, date);
  }

  const findings = computed<TimelineDiagnostic[]>(() => {
    const result: TimelineDiagnostic[] = [];
    const today = toDateString(new Date());

    for (const row of rows.value) {
      const period = periods.value.get(row.id)!;

      if (period.end !== null && period.end < period.start) {
        result.push({ code: 'inverted', severity: 'error', row_ids: [row.id], duty_id: row.duty_id });
      }

      if (row.via_dutiable_id !== null && periods.value.has(row.via_dutiable_id)) {
        const source = periods.value.get(row.via_dutiable_id)!;

        if (source.start !== period.start || source.end !== period.end) {
          result.push({
            code: 'ex_officio_drift',
            severity: 'warning',
            row_ids: [row.id],
            duty_id: row.duty_id,
            detail: { source_id: row.via_dutiable_id },
          });
        }

        continue;
      }

      const cadence = cadenceFor(row, period.start);

      if (cadence !== null && period.end === null && cadence.end_date < today) {
        result.push({
          code: 'open_ended_stale',
          severity: 'warning',
          row_ids: [row.id],
          duty_id: row.duty_id,
          detail: { cadence_id: cadence.id, suggested_end: cadence.end_date },
        });
      }

      // Each edge is measured against its own term: a row covering two cadences would
      // otherwise report its end as a year adrift from the start's term.
      const drift: Record<string, number> = {};
      const cadenceIds: Record<string, string> = {};
      const endCadence = period.end === null ? null : cadenceFor(row, period.end);

      if (cadence !== null) {
        const startDrift = daysBetween(period.start, cadence.start_date);

        if (startDrift > 0 && startDrift <= OFF_CADENCE_MAX_DAYS) {
          drift.start = startDrift;
          cadenceIds.start = cadence.id;
        }
      }

      if (period.end !== null && endCadence !== null) {
        const endDrift = daysBetween(period.end, endCadence.end_date);

        if (endDrift > 0 && endDrift <= OFF_CADENCE_MAX_DAYS) {
          drift.end = endDrift;
          cadenceIds.end = endCadence.id;
        }
      }

      if (Object.keys(drift).length > 0) {
        result.push({
          code: 'off_cadence',
          severity: 'info',
          row_ids: [row.id],
          duty_id: row.duty_id,
          detail: {
            cadence_id: cadenceIds.start ?? cadenceIds.end ?? null,
            cadence_ids: cadenceIds,
            drift_days: drift,
          },
        });
      }

      if (period.end !== null) {
        const covered = applicableCadences(row).filter(
          entry => entry.startDate <= parseTimelineDate(period.end!)
            && entry.endDate >= parseTimelineDate(period.start),
        );

        if (covered.length >= 2) {
          result.push({
            code: 'spans_cadences',
            severity: 'info',
            row_ids: [row.id],
            duty_id: row.duty_id,
            detail: {
              count: covered.length,
              cadence_ids: covered.map(entry => entry.id),
              suggested_start: covered[0].start_date,
              suggested_end: covered[covered.length - 1].end_date,
            },
          });
        }
      }
    }

    result.push(...consecutiveStintFindings());
    result.push(...serverDiagnostics.value.filter(finding => SERVER_ONLY_CODES.has(finding.code)));

    return result;
  });

  /**
   * Overlaps and shared boundaries, from one walk over each holder's stints on one duty.
   * `tenant_id` is part of the key: an owning-tenant row and a cross-tenant representative
   * row for the same person are legitimately concurrent.
   */
  function consecutiveStintFindings(): TimelineDiagnostic[] {
    const groups = new Map<string, ParsedRow[]>();

    for (const row of rows.value) {
      const key = `${row.duty_id}|${row.holder_id}|${row.tenant_id ?? ''}`;
      const bucket = groups.get(key);
      bucket ? bucket.push(row) : groups.set(key, [row]);
    }

    const result: TimelineDiagnostic[] = [];

    for (const group of groups.values()) {
      const sorted = [...group].sort(
        (a, b) => periods.value.get(a.id)!.start.localeCompare(periods.value.get(b.id)!.start),
      );

      for (let i = 1; i < sorted.length; i++) {
        const earlier = sorted[i - 1];
        const later = sorted[i];
        const earlierEnd = periods.value.get(earlier.id)!.end;
        const laterStart = periods.value.get(later.id)!.start;
        const detail = { suggested_end: dayBefore(laterStart) };

        if (earlierEnd === null || earlierEnd > laterStart) {
          result.push({
            code: 'overlap', severity: 'error', row_ids: [earlier.id, later.id], duty_id: earlier.duty_id, detail,
          });
        }
        else if (earlierEnd === laterStart) {
          result.push({
            code: 'boundary_shared', severity: 'warning', row_ids: [earlier.id, later.id], duty_id: earlier.duty_id, detail,
          });
        }
      }
    }

    return result;
  }

  const affectedRowIds = computed(() => new Set(findings.value.flatMap(finding => finding.row_ids)));

  const counts = computed(() => ({
    error: findings.value.filter(finding => finding.severity === 'error').length,
    warning: findings.value.filter(finding => finding.severity === 'warning').length,
    info: findings.value.filter(finding => finding.severity === 'info').length,
  }));

  return { findings, affectedRowIds, counts };
}

/**
 * The single operation that resolves a finding, or null when there is nothing safe to do
 * automatically.
 *
 * `ex_officio_drift` has none on purpose: a derived row's dates are not its own, so the
 * only real fix is moving its source, which is a decision rather than a repair.
 * `orphan_derived_suspect` has none because a NULL `via_dutiable_id` is indistinguishable
 * from a deliberate manual assignment, and these rows grant real permissions.
 * `understaffed` has none because filling a seat is a decision about a person.
 */
export function fixOperationFor(finding: TimelineDiagnostic): TimelineOperation | null {
  const detail = (finding.detail ?? {}) as {
    suggested_start?: string;
    suggested_end?: string;
    cadence_id?: string | null;
  };

  switch (finding.code) {
    case 'inverted':
      return { type: 'set_dates', row_ids: [finding.row_ids[0]], end_date: null };

    case 'overlap':
    case 'boundary_shared':
      return detail.suggested_end
        ? { type: 'set_dates', row_ids: [finding.row_ids[0]], end_date: detail.suggested_end }
        : null;

    case 'open_ended_stale':
      return detail.suggested_end
        ? { type: 'close_open_ended', row_ids: finding.row_ids, end_date: detail.suggested_end }
        : null;

    // No `cadence_id`: the planner resolves each edge's own term, which is the only
    // correct answer once a row's two ends sit in different ones.
    case 'off_cadence':
      return {
        type: 'align_to_cadence',
        row_ids: finding.row_ids,
        edges: 'both',
        threshold_days: OFF_CADENCE_MAX_DAYS,
      };

    // Widen to the outer boundaries of the terms it already covers, rather than forcing
    // it into one — a two-term appointment is a real thing.
    case 'spans_cadences':
      return detail.suggested_start && detail.suggested_end
        ? {
            type: 'set_dates',
            row_ids: finding.row_ids,
            start_date: detail.suggested_start,
            end_date: detail.suggested_end,
          }
        : null;

    default:
      return null;
  }
}
