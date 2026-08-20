import { computed, ref, type Ref } from 'vue';

import type { ParsedRow, TimelineGroup, TimelineLayoutRow } from '../types';

export const GROUP_HEADER_HEIGHT = 26;
export const ROW_HEIGHT = 22;

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
) {
  const collapsed = ref(new Set<string>());

  const rowsByGroup = computed(() => {
    const map = new Map<string, ParsedRow[]>();

    for (const row of rows.value) {
      const bucket = map.get(row.group_key);
      bucket ? bucket.push(row) : map.set(row.group_key, [row]);
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
    rowTop,
    rowHeightFor,
    rowIndex,
    toggleGroup,
    setAllCollapsed,
  };
}
