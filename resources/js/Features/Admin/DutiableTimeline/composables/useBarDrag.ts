import { computed, onUnmounted, type ComputedRef, type Ref } from 'vue';
import * as d3 from 'd3';
import { addMonths, startOfDay, startOfMonth } from 'date-fns';

import { parseTimelineDate, toDateString } from './useDutiableTimelineData';
import { applicableCadences } from '../cadencePools';
import { DRAG_THRESHOLD_PX, SNAP_PX } from '../constants';
import type { ParsedCadence, ParsedRow, StagedDates } from '../types';

export type DragMode = 'body' | 'start' | 'end';

/**
 * How many columns a horizontal drag crossed. Rounded, so the bar commits to a whole
 * month rather than landing between two.
 */
export function monthDelta(dx: number, monthWidthPx: number): number {
  return Math.round(dx / monthWidthPx);
}

/**
 * Moving a bar shifts both ends by whole months and keeps the day of month, so a term
 * that starts on the 18th still starts on the 18th afterwards. This single rule is what
 * guarantees an unrelated drag can never destroy a deliberately non-boundary date.
 *
 * date-fns clamps rather than overflows (2024-01-31 + 1 month = 2024-02-29); the ghost
 * label shows the clamped value, so it is visible rather than silent.
 */
export function shiftByMonths(
  period: { start: Date; end: Date | null },
  deltaMonths: number,
): { start: Date; end: Date | null } {
  return {
    start: addMonths(period.start, deltaMonths),
    end: period.end ? addMonths(period.end, deltaMonths) : null,
  };
}

/**
 * The closest boundary within `snapPx` of where the pointer actually is, or null when
 * nothing is near enough — in which case the drag keeps day precision.
 */
export function nearestSnap(
  candidates: Date[],
  px: number,
  toPixels: (date: Date) => number,
  snapPx: number,
): Date | null {
  let best: Date | null = null;
  let bestDistance = snapPx;

  for (const candidate of candidates) {
    const distance = Math.abs(toPixels(candidate) - px);

    if (distance <= bestDistance) {
      best = candidate;
      bestDistance = distance;
    }
  }

  return best;
}

export interface DragEntry {
  rowId: string;
  start: Date;
  end: Date | null;
}

export interface BarDragState {
  mode: DragMode;
  /** The row the gesture started on; the ghost label follows this one. */
  anchorRowId: string;
  entries: DragEntry[];
  /** Only set while the pointer is inside SNAP_PX of a boundary, and Alt is not held. */
  snapTarget: Date | null;
  freehand: boolean;
}

interface BarDragOptions {
  svg: Ref<SVGSVGElement | null>;
  scale: ComputedRef<d3.ScaleTime<number, number>>;
  monthWidthPx: Ref<number>;
  rows: Ref<ParsedRow[]>;
  cadences: Ref<ParsedCadence[]>;
  staged: Ref<Map<string, StagedDates>>;
  selectedIds: Ref<Set<string>>;
  onUpdate: (state: BarDragState | null) => void;
  onCommit: (entries: DragEntry[]) => void;
}

/**
 * Pointer dragging for the timeline bars.
 *
 * One delegated `pointerdown` on the chart SVG rather than per-bar handlers: `render()`
 * rebuilds every `<g>` from scratch, so anything bound to a bar would be thrown away on
 * the next frame.
 *
 * Two gestures, deliberately different:
 * - **body** moves by whole months and keeps the day of month, so dragging a term that
 *   starts on the 18th never quietly turns it into the 1st;
 * - **edge** moves one date, snapping to a cadence boundary / month start / sibling date /
 *   today when it lands within SNAP_PX, and staying exact otherwise.
 *
 * Modifiers: Alt turns all snapping off, Ctrl/Cmd applies the move to every selected row,
 * Esc cancels. Shift is left alone on purpose — it means "create a check-in range" in the
 * meetings chart and re-purposing it here would be a cross-feature trap.
 */
export function useBarDrag(options: BarDragOptions) {
  const { svg, scale, monthWidthPx, rows, cadences, staged, selectedIds, onUpdate, onCommit } = options;

  const rowsById = computed(() => {
    const map = new Map<string, ParsedRow>();
    for (const row of rows.value) map.set(row.id, row);

    return map;
  });

  /**
   * Cadence edges and today. The edges are the dragged row's *own* ladder: snapping an
   * override institution's seat to a global July boundary it does not use is exactly the
   * drift this editor exists to remove.
   */
  function cadenceSnapTargets(rowId: string): Date[] {
    const row = rowsById.value.get(rowId);

    return applicableCadences(cadences.value, row?.institution_id ?? null)
      .flatMap(cadence => [cadence.startDate, cadence.endDate]);
  }

  function resolved(row: ParsedRow): { start: Date; end: Date | null } {
    const override = staged.value.get(row.id);

    if (!override) return { start: row.startDate, end: row.endDate };

    return {
      start: parseTimelineDate(override.start_date),
      end: override.end_date ? parseTimelineDate(override.end_date) : null,
    };
  }

  /** The other stints the same person holds on the same duty — "abut the predecessor". */
  function siblingDates(rowId: string): Date[] {
    const row = rowsById.value.get(rowId);
    if (!row) return [];

    return rows.value
      .filter(other => other.id !== rowId && other.duty_id === row.duty_id && other.holder_id === row.holder_id)
      .flatMap((other) => {
        const { start, end } = resolved(other);

        return end ? [start, end] : [start];
      });
  }

  let state: BarDragState | null = null;
  let armed: { rowId: string; mode: DragMode; originX: number; rowIds: string[] } | null = null;

  function editableIdsFor(rowId: string, event: PointerEvent): string[] {
    const applyToSelection = event.ctrlKey || event.metaKey;

    if (!applyToSelection) return [rowId];

    const ids = [...selectedIds.value].filter(id => rowsById.value.get(id)?.editable);

    return ids.includes(rowId) ? ids : [...ids, rowId];
  }

  function onPointerDown(event: PointerEvent): void {
    if (event.button !== 0) return;

    const target = event.target as Element | null;
    const bar = target?.closest?.('g.dutiable-bar') as SVGGElement | null;
    const rowId = bar?.dataset.rowId;

    if (!rowId || !rowsById.value.get(rowId)?.editable) return;

    const handle = target?.closest?.('[data-handle]') as SVGElement | null;
    const mode = (handle?.dataset.handle as DragMode | undefined) ?? 'body';

    armed = { rowId, mode, originX: event.clientX, rowIds: editableIdsFor(rowId, event) };

    document.addEventListener('pointermove', onPointerMove);
    document.addEventListener('pointerup', onPointerUp);
    document.addEventListener('keydown', onKeyDown);
  }

  function onPointerMove(event: PointerEvent): void {
    if (!armed) return;

    const dx = event.clientX - armed.originX;

    // Below the threshold the gesture is still a click; promoting early would make every
    // selection nudge a date.
    if (state === null && Math.abs(dx) < DRAG_THRESHOLD_PX) return;

    state = armed.mode === 'body'
      ? planBodyDrag(armed.rowIds, armed.rowId, dx)
      : planEdgeDrag(armed.rowIds, armed.rowId, armed.mode, dx, event.altKey);

    onUpdate(state);
  }

  function planBodyDrag(rowIds: string[], anchorRowId: string, dx: number): BarDragState {
    const deltaMonths = monthDelta(dx, monthWidthPx.value);

    const entries = rowIds.flatMap<DragEntry>((rowId) => {
      const row = rowsById.value.get(rowId);

      return row ? [{ rowId, ...shiftByMonths(resolved(row), deltaMonths) }] : [];
    });

    return { mode: 'body', anchorRowId, entries, snapTarget: null, freehand: false };
  }

  function planEdgeDrag(rowIds: string[], anchorRowId: string, mode: DragMode, dx: number, freehand: boolean): BarDragState {
    const anchor = rowsById.value.get(anchorRowId)!;
    const { start, end } = resolved(anchor);
    const base = mode === 'start' ? start : (end ?? start);

    const px = scale.value(base) + dx;
    const raw = startOfDay(scale.value.invert(px));
    const snapTarget = freehand ? null : nearestSnapTarget(anchorRowId, px);
    const date = snapTarget ?? raw;

    const entries = rowIds.flatMap<DragEntry>((rowId) => {
      const row = rowsById.value.get(rowId);
      if (!row) return [];

      const current = resolved(row);
      const next = mode === 'start'
        ? { rowId, start: date, end: current.end }
        : { rowId, start: current.start, end: date };

      // An inverted row is refused server-side anyway; refusing it here keeps the ghost
      // from ever drawing a bar with negative width.
      if (next.end !== null && next.end < next.start) return [];

      return [next];
    });

    return { mode, anchorRowId, entries, snapTarget, freehand };
  }

  function nearestSnapTarget(rowId: string, px: number): Date | null {
    return nearestSnap(
      [
        ...cadenceSnapTargets(rowId),
        startOfDay(new Date()),
        ...siblingDates(rowId),
        ...monthBoundariesAround(px),
      ],
      px,
      date => scale.value(date),
      SNAP_PX,
    );
  }

  /** The 1st of the month the pointer is in, and of the next one. */
  function monthBoundariesAround(px: number): Date[] {
    const at = scale.value.invert(px);
    const first = startOfMonth(at);

    return [first, addMonths(first, 1)];
  }

  function onKeyDown(event: KeyboardEvent): void {
    if (event.key !== 'Escape') return;

    event.preventDefault();
    cancel();
  }

  function onPointerUp(): void {
    const finished = state;

    teardown();

    if (finished && finished.entries.length > 0) {
      onCommit(finished.entries);
    }
  }

  function cancel(): void {
    teardown();
  }

  function teardown(): void {
    state = null;
    armed = null;

    document.removeEventListener('pointermove', onPointerMove);
    document.removeEventListener('pointerup', onPointerUp);
    document.removeEventListener('keydown', onKeyDown);

    onUpdate(null);
  }

  function attach(): void {
    svg.value?.addEventListener('pointerdown', onPointerDown);
  }

  function detach(): void {
    svg.value?.removeEventListener('pointerdown', onPointerDown);
    teardown();
  }

  onUnmounted(detach);

  return { attach, detach, cancel };
}

/** Shared by the ghost label and the tests: how a pending move reads as text. */
export function describeDragEntry(entry: DragEntry): string {
  return entry.end
    ? `${toDateString(entry.start)} → ${toDateString(entry.end)}`
    : `${toDateString(entry.start)} → …`;
}
