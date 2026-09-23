import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

import type { CollectionColumn } from '@/Components/Collection/types';
import { SELECT_COLUMN_ID, useCollectionTable } from '../useCollectionTable';

interface Row {
  id: string;
  title: string;
  locked?: boolean;
}

const columns: CollectionColumn[] = [
  { key: 'title', label: 'Pavadinimas', sortField: 'title' },
  { key: 'date', label: 'Data', sortField: 'created_at' },
  { key: 'actions', label: 'Veiksmai', pinned: true },
];

function makeTable(options: { selectable?: boolean; sortBy?: string; rows?: Row[] } = {}) {
  const selection = ref<string[]>([]);
  const setSortBy = vi.fn();
  const items = ref<Row[]>(options.rows ?? [{ id: 'a', title: 'A' }, { id: 'b', title: 'B', locked: true }]);

  const result = useCollectionTable<Row>({
    collection: 'test-rows',
    items,
    itemKey: row => row.id,
    columns: ref(columns),
    selectable: ref(options.selectable ?? false),
    canSelect: row => !row.locked,
    selection,
    sortBy: ref(options.sortBy ?? 'title:asc'),
    // `created_at` has only one direction, so its header is not a toggle.
    sortOptions: ref([
      { value: 'title:asc', label: 'A–Z' },
      { value: 'title:desc', label: 'Z–A' },
      { value: 'created_at:desc', label: 'Naujausi' },
    ]),
    setSortBy,
  });

  return { ...result, selection, setSortBy };
}

describe('useCollectionTable', () => {
  beforeEach(() => localStorage.clear());

  it('makes a header sortable only when the source offers both directions', () => {
    const { table } = makeTable();

    expect(table.getColumn('title')?.getCanSort()).toBe(true);
    expect(table.getColumn('date')?.getCanSort()).toBe(false);
    expect(table.getColumn('title')?.getIsSorted()).toBe('asc');
  });

  it('hands a header sort to the source instead of sorting rows itself', () => {
    const { table, setSortBy } = makeTable();

    table.getColumn('title')?.toggleSorting(true);

    expect(setSortBy).toHaveBeenCalledWith('title:desc');
    expect(table.getRowModel().rows.map(row => row.id)).toEqual(['a', 'b']);
  });

  it('adds a selection column and skips rows that may not be selected', () => {
    const { table, selection } = makeTable({ selectable: true });

    expect(table.getAllLeafColumns()[0].id).toBe(SELECT_COLUMN_ID);

    table.toggleAllRowsSelected(true);

    expect(selection.value).toEqual(['a']);
    expect(table.getRow('b').getCanSelect()).toBe(false);
  });

  it('keeps the first and pinned columns out of the column menu and remembers hidden ones', async () => {
    const { table, hideableColumns } = makeTable();

    expect(hideableColumns.value.map(column => column.key)).toEqual(['date']);

    table.getColumn('date')?.toggleVisibility(false);
    await nextTick();

    expect(JSON.parse(localStorage.getItem('admin-collection-columns:test-rows') ?? '{}')).toEqual({ date: false });
  });

  describe('shift-click range selection', () => {
    const rows: Row[] = ['a', 'b', 'c', 'd', 'e'].map(id => ({ id, title: id.toUpperCase(), locked: id === 'c' }));

    it('selects every selectable row between the anchor and the clicked row, in either direction', () => {
      const { toggleRow, selection } = makeTable({ selectable: true, rows });

      toggleRow('e', true);
      toggleRow('b', true, { range: true });

      expect([...selection.value].sort()).toEqual(['b', 'd', 'e']);
    });

    it('clears the range when the clicked row is being unticked', () => {
      const { table, toggleRow, selection } = makeTable({ selectable: true, rows });
      table.toggleAllRowsSelected(true);

      toggleRow('a', false);
      toggleRow('d', false, { range: true });

      expect(selection.value).toEqual(['e']);
    });

    it('toggles only the clicked row when there is no anchor yet', () => {
      const { toggleRow, selection } = makeTable({ selectable: true, rows });

      toggleRow('d', true, { range: true });

      expect(selection.value).toEqual(['d']);
    });
  });
});
