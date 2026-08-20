import { describe, expect, it } from 'vitest';
import { nextTick, ref } from 'vue';

import { useDutiableStaging } from '../composables/useDutiableStaging';
import type { ParsedRow } from '../types';

function makeRow(overrides: Partial<ParsedRow> = {}): ParsedRow {
  const base: ParsedRow = {
    id: 'row-1',
    group_key: 'user:u1',
    duty_id: 'duty-1',
    duty_name: 'Parlamento narys',
    institution_id: 'inst-1',
    institution_name: 'Parlamentas',
    holder_id: 'u1',
    holder_name: 'Vardas',
    holder_photo: null,
    tenant_id: null,
    tenant_shortname: null,
    start_date: '2024-05-18',
    end_date: '2025-05-17',
    via_dutiable_id: null,
    source: null,
    derived_ids: [],
    is_derived: false,
    editable: true,
    edit_url: '/mano/dutiables/row-1/edit',
    startDate: new Date(2024, 4, 18, 12),
    endDate: new Date(2025, 4, 17, 12),
  };

  return { ...base, ...overrides };
}

describe('useDutiableStaging', () => {
  it('replaces the Map rather than mutating it, so the chart re-renders', () => {
    const rows = ref([makeRow()]);
    const staging = useDutiableStaging(rows);
    const before = staging.staged.value;

    staging.stage('row-1', { start_date: '2024-07-18', end_date: '2025-07-17' });

    expect(staging.staged.value).not.toBe(before);
  });

  it('drops an edit that lands back on the persisted dates', () => {
    const rows = ref([makeRow()]);
    const staging = useDutiableStaging(rows);

    staging.stage('row-1', { start_date: '2024-07-18', end_date: '2025-05-17' });
    expect(staging.dirtyCount.value).toBe(1);

    staging.stage('row-1', { start_date: '2024-05-18', end_date: '2025-05-17' });
    expect(staging.dirtyCount.value).toBe(0);
    expect(staging.isDirty.value).toBe(false);
  });

  it('treats making a row open-ended as a real edit', () => {
    const rows = ref([makeRow()]);
    const staging = useDutiableStaging(rows);

    staging.stage('row-1', { start_date: '2024-05-18', end_date: null });

    expect(staging.dirtyCount.value).toBe(1);
    expect(staging.operations.value[0]).toEqual({
      type: 'set_dates',
      row_ids: ['row-1'],
      start_date: '2024-05-18',
      end_date: null,
    });
  });

  it('projects an ex-officio row onto its staged source, and keeps it out of the operations', () => {
    const source = makeRow({ derived_ids: ['row-2'] });
    const derived = makeRow({
      id: 'row-2', via_dutiable_id: 'row-1', is_derived: true, editable: false,
    });

    const staging = useDutiableStaging(ref([source, derived]));

    staging.stage('row-1', { start_date: '2024-07-18', end_date: '2025-07-17' });

    expect(staging.staged.value.get('row-2')).toEqual({
      start_date: '2024-07-18', end_date: '2025-07-17', projected: true,
    });
    expect(staging.dirtyCount.value).toBe(1);
    expect(staging.operations.value.map(operation => operation.row_ids)).toEqual([['row-1']]);
  });

  it('drops the projection again when the source edit is reverted', () => {
    const source = makeRow({ derived_ids: ['row-2'] });
    const derived = makeRow({ id: 'row-2', via_dutiable_id: 'row-1', is_derived: true });

    const staging = useDutiableStaging(ref([source, derived]));

    staging.stage('row-1', { start_date: '2024-07-18', end_date: '2025-07-17' });
    staging.revert('row-1');

    expect(staging.staged.value.size).toBe(0);
  });

  it('emits one set_dates per row so the server can re-plan each move exactly', () => {
    const staging = useDutiableStaging(ref([makeRow(), makeRow({ id: 'row-2' })]));

    staging.stageMany([
      { rowId: 'row-1', dates: { start_date: '2024-07-18', end_date: '2025-07-17' } },
      { rowId: 'row-2', dates: { start_date: '2024-09-01', end_date: null } },
    ]);

    expect(staging.operations.value).toHaveLength(2);
    expect(staging.operations.value.every(operation => operation.type === 'set_dates')).toBe(true);
  });

  it('keeps the ex-officio projection drawn after a commit, until the database agrees', async () => {
    const source = makeRow({ derived_ids: ['row-2'] });
    const derived = makeRow({ id: 'row-2', via_dutiable_id: 'row-1', is_derived: true });
    const rows = ref([source, derived]);
    const staging = useDutiableStaging(rows);

    staging.stage('row-1', { start_date: '2024-07-18', end_date: '2025-07-17' });

    // The commit succeeded: the source row is saved, but SyncExOfficioDutiables is queued
    // ($afterCommit), so the derived row is still stale in the database.
    staging.settle();

    expect(staging.dirtyCount.value).toBe(0);
    expect(staging.pending.value.get('row-2')?.start_date).toBe('2024-07-18');
    expect(staging.drawn.value.get('row-2')?.start_date).toBe('2024-07-18');

    // A refetch that shows the queue has run drops the hint.
    rows.value = [
      makeRow({ start_date: '2024-07-18', end_date: '2025-07-17', derived_ids: ['row-2'] }),
      makeRow({
        id: 'row-2', via_dutiable_id: 'row-1', is_derived: true,
        start_date: '2024-07-18', end_date: '2025-07-17',
      }),
    ];
    await nextTick();

    expect(staging.pending.value.size).toBe(0);
  });

  it('revertAll clears everything, projections included', () => {
    const staging = useDutiableStaging(ref([makeRow({ derived_ids: ['row-2'] }), makeRow({ id: 'row-2' })]));

    staging.stage('row-1', { start_date: '2024-07-18', end_date: '2025-07-17' });
    staging.revertAll();

    expect(staging.staged.value.size).toBe(0);
    expect(staging.isDirty.value).toBe(false);
  });
});
