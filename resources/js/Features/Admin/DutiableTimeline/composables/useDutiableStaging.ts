import { computed, shallowRef, watch, type Ref } from 'vue';

import type { ParsedRow, StagedDates, TimelineOperation } from '../types';

/**
 * Unsaved edits, held client-side until one confirmed batch commits them.
 *
 * Nothing is written per gesture: `guardSelfLockout` is an all-or-nothing envelope
 * (AccessChangeAnalyzer::apply opens one transaction and rolls the whole closure back),
 * and a per-drag write would also queue one SyncExOfficioDutiables per intermediate value.
 *
 * The Map is always **replaced**, never mutated — DutiableGantt watches `staged` by
 * identity, so an in-place `set()` would stage an edit the chart never draws.
 */
export function useDutiableStaging(rows: Ref<ParsedRow[]>) {
  const staged = shallowRef(new Map<string, StagedDates>());

  /**
   * Ex-officio rows whose source has been saved but whose own row the queued
   * SyncExOfficioDutiables has not caught up with yet ($afterCommit = true). Kept drawn
   * at their projected dates so a refetch shows "syncing" rather than the old value
   * flickering back for a second.
   */
  const pending = shallowRef(new Map<string, StagedDates>());

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

  function withProjections(next: Map<string, StagedDates>): Map<string, StagedDates> {
    const result = new Map<string, StagedDates>();

    for (const [rowId, dates] of next) {
      if (dates.projected) continue;

      result.set(rowId, dates);
    }

    // An ex-officio row mirrors its source, exactly as SyncExOfficioDutiables does after
    // the queue drains. Projecting it here is what makes the knock-on visible pre-commit.
    for (const [rowId, dates] of result) {
      for (const derivedId of byId.value.get(rowId)?.derived_ids ?? []) {
        if (result.has(derivedId)) continue;

        result.set(derivedId, { ...dates, projected: true });
      }
    }

    return result;
  }

  function commit(next: Map<string, StagedDates>): void {
    staged.value = withProjections(next);
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

  /**
   * After a successful commit: the rows the user moved are saved, so their edits drop
   * out, but the ex-officio rows that follow them are still only projected.
   */
  function settle(): void {
    const next = new Map<string, StagedDates>();

    for (const [rowId, dates] of staged.value) {
      if (dates.projected) next.set(rowId, { ...dates });
    }

    pending.value = next;
    staged.value = new Map();
  }

  // A pending row stops being pending the moment the database agrees with it.
  watch(rows, () => {
    if (pending.value.size === 0) return;

    const next = new Map(pending.value);

    for (const [rowId, dates] of pending.value) {
      const row = byId.value.get(rowId);

      if (row === undefined || (row.start_date === dates.start_date && (row.end_date ?? null) === dates.end_date)) {
        next.delete(rowId);
      }
    }

    if (next.size !== pending.value.size) pending.value = next;
  });

  /** What the chart draws: staged edits on top of anything still syncing. */
  const drawn = computed(() => {
    if (pending.value.size === 0) return staged.value;

    return new Map([...pending.value, ...staged.value]);
  });

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
    staged, drawn, pending, stage, stageMany, revert, revertAll, settle,
    resolved, editedIds, dirtyCount, isDirty, operations,
  };
}
