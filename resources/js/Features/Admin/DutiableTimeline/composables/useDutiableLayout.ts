import { computed, ref, type Ref } from 'vue';

import type { ParsedRow, TimelineGroup, TimelineLayoutRow } from '../types';

export const GROUP_HEADER_HEIGHT = 26;
export const ROW_HEIGHT = 22;

/** How the rows inside a group are ordered. `default` keeps the server's ordering. */
export type RowSortMode = 'default' | 'study_program';

/** What a collapsed group still says about the rows it hides. */
export interface GroupSummary {
  count: number;
  start: Date;
  /** `null` when at least one row is open-ended — the merged bar runs off the right edge. */
  end: Date | null;
}

/**
 * Turns groups + rows into positioned lanes.
 *
 * Emits the same `{ key, type, top, height }` shape the shared Graphs renderers expect,
 * with `type: 'tenant'` reserved for group headers so `renderBackground` skips its grid
 * lines on them, exactly as it does for tenant rows in the meetings chart.
 */
export function useDutiableLayout(
  groups: Ref<TimelineGroup[]>,
  rows: Ref<ParsedRow[]>,
  sortMode: Ref<RowSortMode> = ref('default'),
) {
  const collapsed = ref(new Set<string>());

  const rowsByGroup = computed(() => {
    const map = new Map<string, ParsedRow[]>();

    for (const row of rows.value) {
      const bucket = map.get(row.group_key);
      bucket ? bucket.push(row) : map.set(row.group_key, [row]);
    }

    if (sortMode.value === 'study_program') {
      // A programme is an optional per-assignment override, so most charts have a mix.
      // Rows without one go last rather than clumping at the top under an empty key.
      // Array.sort is stable, so rows sharing a programme keep the server's ordering.
      for (const bucket of map.values()) {
        bucket.sort(byProgramme);
      }
    }

    return map;
  });

  const layoutRows = computed<TimelineLayoutRow[]>(() => {
    const result: TimelineLayoutRow[] = [];
    let top = 0;

    for (const group of groups.value) {
      result.push({ key: group.key, type: 'tenant', top, height: GROUP_HEADER_HEIGHT, group });
      top += GROUP_HEADER_HEIGHT;

      if (collapsed.value.has(group.key)) continue;

      for (const row of rowsByGroup.value.get(group.key) ?? []) {
        result.push({ key: row.id, type: 'row', top, height: ROW_HEIGHT, group, row });
        top += ROW_HEIGHT;
      }
    }

    return result;
  });

  /**
   * What each group covers, computed whether or not it is collapsed — a header that hides
   * its rows should still say how many there are and when they ran.
   */
  const groupSummaries = computed(() => {
    const map = new Map<string, GroupSummary>();

    for (const [key, bucket] of rowsByGroup.value) {
      if (bucket.length === 0) continue;

      const openEnded = bucket.some(row => row.endDate === null);

      map.set(key, {
        count: bucket.length,
        start: new Date(Math.min(...bucket.map(row => row.startDate.getTime()))),
        end: openEnded
          ? null
          : new Date(Math.max(...bucket.map(row => row.endDate!.getTime()))),
      });
    }

    return map;
  });

  const totalHeight = computed(() => layoutRows.value.reduce((sum, r) => sum + r.height, 0));

  const positionByKey = computed(() => {
    const map = new Map<string, TimelineLayoutRow>();
    for (const row of layoutRows.value) map.set(row.key, row);

    return map;
  });

  const indexByKey = computed(() => {
    const map = new Map<string, number>();
    layoutRows.value.forEach((row, index) => map.set(row.key, index));

    return map;
  });

  const rowTop = (key: string | number): number => positionByKey.value.get(String(key))?.top ?? 0;
  const rowHeightFor = (key: string | number): number => positionByKey.value.get(String(key))?.height ?? ROW_HEIGHT;
  const rowIndex = (key: string | number): number => indexByKey.value.get(String(key)) ?? 0;

  function toggleGroup(key: string): void {
    const next = new Set(collapsed.value);
    next.has(key) ? next.delete(key) : next.add(key);
    collapsed.value = next;
  }

  function setAllCollapsed(value: boolean): void {
    collapsed.value = value ? new Set(groups.value.map(g => g.key)) : new Set();
  }

  return {
    collapsed,
    layoutRows,
    totalHeight,
    rowsByGroup,
    groupSummaries,
    rowTop,
    rowHeightFor,
    rowIndex,
    toggleGroup,
    setAllCollapsed,
  };
}

/**
 * Presence is tested before the names are compared: a sentinel string would have to survive
 * locale collation, which is not something to bet an ordering on.
 */
function byProgramme(a: ParsedRow, b: ParsedRow): number {
  const left = a.extras?.study_program ?? '';
  const right = b.extras?.study_program ?? '';

  if (left === right) return 0;
  if (left === '') return 1;
  if (right === '') return -1;

  return left.localeCompare(right);
}
