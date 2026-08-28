import { describe, expect, it } from 'vitest';
import { ref } from 'vue';

import {
  GROUP_HEADER_HEIGHT, ROW_HEIGHT, useDutiableLayout, type RowSortMode,
} from '../composables/useDutiableLayout';
import type { ParsedRow, TimelineGroup } from '../types';

function makeRow(id: string, groupKey: string, overrides: Partial<ParsedRow> = {}): ParsedRow {
  return {
    id,
    group_key: groupKey,
    duty_id: 'duty-1',
    duty_name: 'Parlamento narys',
    institution_id: 'inst-1',
    institution_name: 'Parlamentas',
    holder_id: 'user-1',
    holder_name: 'Vardas Pavardė',
    holder_photo: null,
    tenant_id: null,
    tenant_shortname: null,
    start_date: '2025-07-01',
    end_date: '2026-06-30',
    via_dutiable_id: null,
    extras: null,
    source: null,
    derived_ids: [],
    is_derived: false,
    editable: true,
    edit_url: '/mano/dutiables/1/edit',
    startDate: new Date(2025, 6, 1, 12),
    endDate: new Date(2026, 5, 30, 12),
    ...overrides,
  };
}

const group = (key: string): TimelineGroup => ({ key, kind: 'user', label: key });

describe('useDutiableLayout', () => {
  it('emits a header lane per group followed by its rows', () => {
    const groups = ref([group('user:a'), group('user:b')]);
    const rows = ref([makeRow('1', 'user:a'), makeRow('2', 'user:a'), makeRow('3', 'user:b')]);

    const { layoutRows } = useDutiableLayout(groups, rows);

    expect(layoutRows.value.map(l => [l.key, l.type])).toEqual([
      ['user:a', 'tenant'],
      ['1', 'row'],
      ['2', 'row'],
      ['user:b', 'tenant'],
      ['3', 'row'],
    ]);
  });

  it('stacks lanes without gaps', () => {
    const groups = ref([group('user:a')]);
    const rows = ref([makeRow('1', 'user:a'), makeRow('2', 'user:a')]);

    const { layoutRows, totalHeight } = useDutiableLayout(groups, rows);

    expect(layoutRows.value.map(l => l.top)).toEqual([
      0,
      GROUP_HEADER_HEIGHT,
      GROUP_HEADER_HEIGHT + ROW_HEIGHT,
    ]);
    expect(totalHeight.value).toBe(GROUP_HEADER_HEIGHT + ROW_HEIGHT * 2);
  });

  /**
   * Collapsing is the main lever for the 176-row Parlamentas duty, so the header must
   * survive while its rows disappear — otherwise the group cannot be reopened.
   */
  it('drops a collapsed group\'s rows but keeps its header', () => {
    const groups = ref([group('user:a'), group('user:b')]);
    const rows = ref([makeRow('1', 'user:a'), makeRow('2', 'user:b')]);

    const { layoutRows, toggleGroup } = useDutiableLayout(groups, rows);
    toggleGroup('user:a');

    expect(layoutRows.value.map(l => l.key)).toEqual(['user:a', 'user:b', '2']);
  });

  it('reports the absolute lane index so zebra parity survives culling', () => {
    const groups = ref([group('user:a')]);
    const rows = ref([makeRow('1', 'user:a'), makeRow('2', 'user:a')]);

    const { rowIndex, rowTop, rowHeightFor } = useDutiableLayout(groups, rows);

    expect(rowIndex('2')).toBe(2);
    expect(rowTop('2')).toBe(GROUP_HEADER_HEIGHT + ROW_HEIGHT);
    expect(rowHeightFor('2')).toBe(ROW_HEIGHT);
  });

  it('collapses and expands every group at once', () => {
    const groups = ref([group('user:a'), group('user:b')]);
    const rows = ref([makeRow('1', 'user:a')]);

    const { collapsed, setAllCollapsed } = useDutiableLayout(groups, rows);

    setAllCollapsed(true);
    expect(collapsed.value.size).toBe(2);

    setAllCollapsed(false);
    expect(collapsed.value.size).toBe(0);
  });

  describe('study-programme sort', () => {
    const withProgramme = (id: string, programme: string | null) => makeRow(id, 'user:a', {
      extras: programme === null ? null : { study_program: programme },
    });

    it('orders a group by programme, leaving rows without one last', () => {
      const groups = ref([group('user:a')]);
      const rows = ref([
        withProgramme('none', null),
        withProgramme('sociologija', 'Sociologija'),
        withProgramme('programu', 'Programų sistemos'),
      ]);
      const sortMode = ref<RowSortMode>('study_program');

      const { layoutRows } = useDutiableLayout(groups, rows, sortMode);

      expect(layoutRows.value.filter(l => l.row).map(l => l.key))
        .toEqual(['programu', 'sociologija', 'none']);
    });

    it('keeps the server ordering while the sort is off', () => {
      const groups = ref([group('user:a')]);
      const rows = ref([withProgramme('sociologija', 'Sociologija'), withProgramme('programu', 'Programų sistemos')]);

      const { layoutRows } = useDutiableLayout(groups, rows);

      expect(layoutRows.value.filter(l => l.row).map(l => l.key)).toEqual(['sociologija', 'programu']);
    });
  });

  describe('group summaries', () => {
    it('spans the earliest start to the latest end', () => {
      const groups = ref([group('user:a')]);
      const rows = ref([
        makeRow('1', 'user:a', { startDate: new Date(2023, 6, 1, 12), endDate: new Date(2024, 5, 30, 12) }),
        makeRow('2', 'user:a', { startDate: new Date(2024, 6, 1, 12), endDate: new Date(2025, 5, 30, 12) }),
      ]);

      const { groupSummaries } = useDutiableLayout(groups, rows);
      const summary = groupSummaries.value.get('user:a')!;

      expect(summary.count).toBe(2);
      expect(summary.start).toEqual(new Date(2023, 6, 1, 12));
      expect(summary.end).toEqual(new Date(2025, 5, 30, 12));
    });

    it('reports an open end when any row is still running', () => {
      const groups = ref([group('user:a')]);
      const rows = ref([makeRow('1', 'user:a', { endDate: null })]);

      const { groupSummaries } = useDutiableLayout(groups, rows);

      expect(groupSummaries.value.get('user:a')!.end).toBeNull();
    });

    it('summarises a collapsed group too — that is the only time it is read', () => {
      const groups = ref([group('user:a')]);
      const rows = ref([makeRow('1', 'user:a'), makeRow('2', 'user:a')]);

      const { groupSummaries, toggleGroup } = useDutiableLayout(groups, rows);
      toggleGroup('user:a');

      expect(groupSummaries.value.get('user:a')!.count).toBe(2);
    });
  });

  it('ignores rows whose group is not in the group list', () => {
    const groups = ref([group('user:a')]);
    const rows = ref([makeRow('1', 'user:a'), makeRow('99', 'user:missing')]);

    const { layoutRows } = useDutiableLayout(groups, rows);

    expect(layoutRows.value.map(l => l.key)).toEqual(['user:a', '1']);
  });
});
