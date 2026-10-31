import { computed, shallowRef, type Ref } from 'vue';

import type { ParsedRow, StagedDates, TimelineOperation } from '../types';

/** Derived dates are previewed by the server planner; local gestures stage root rows only. */
export function useDutiableStaging(rows: Ref<ParsedRow[]>) {
  const staged = shallowRef(new Map<string, StagedDates>());

  const byId = computed(() => {
    const map = new Map<string, ParsedRow>();
    for (const row of rows.value) map.set(row.id, row);

    return map;
  });

  /** An edit back to the persisted value is not an edit; it drops out of the batch. */
  function matchesPersisted(rowId: string, dates: StagedDates): boolean {
    const row = byId.value.get(rowId);

    return row !== undefined
      && row.start_date === dates.start_date
      && (row.end_date ?? null) === dates.end_date;
  }

  function commit(next: Map<string, StagedDates>): void {
    staged.value = next;
  }

  function stage(rowId: string, dates: StagedDates): void {
    const next = new Map(staged.value);

    if (matchesPersisted(rowId, dates)) {
      next.delete(rowId);
    }
    else {
      next.set(rowId, { start_date: dates.start_date, end_date: dates.end_date });
    }

    commit(next);
  }

  function stageMany(entries: Array<{ rowId: string; dates: StagedDates }>): void {
    const next = new Map(staged.value);

    for (const { rowId, dates } of entries) {
      if (matchesPersisted(rowId, dates)) {
        next.delete(rowId);
      }
      else {
        next.set(rowId, { start_date: dates.start_date, end_date: dates.end_date });
      }
    }

    commit(next);
  }

  function revert(rowId: string): void {
    const next = new Map(staged.value);
    next.delete(rowId);
    commit(next);
  }

  function revertAll(): void {
    staged.value = new Map();
  }

  function settle(): void {
    staged.value = new Map();
  }

  const drawn = computed(() => staged.value);

  /** The dates a row should be drawn with right now — staged if any, persisted otherwise. */
  function resolved(row: ParsedRow): StagedDates {
    return drawn.value.get(row.id) ?? { start_date: row.start_date, end_date: row.end_date };
  }

  const editedIds = computed(() => [...staged.value]
    .filter(([, dates]) => !dates.projected)
    .map(([rowId]) => rowId));

  const dirtyCount = computed(() => editedIds.value.length);
  const isDirty = computed(() => dirtyCount.value > 0);

  /**
   * One `set_dates` per row: the dates differ per row after a drag, and the server
   * re-plans from these rather than trusting any diff the client drew.
   */
  const operations = computed<TimelineOperation[]>(() => editedIds.value.map((rowId) => {
    const dates = staged.value.get(rowId)!;

    return {
      type: 'set_dates' as const,
      row_ids: [rowId],
      start_date: dates.start_date,
      end_date: dates.end_date,
    };
  }));

  return {
    staged, drawn, stage, stageMany, revert, revertAll, settle,
    resolved, editedIds, dirtyCount, isDirty, operations,
  };
}
