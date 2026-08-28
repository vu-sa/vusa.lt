import { describe, expect, it } from 'vitest';
import { ref } from 'vue';

import { fixOperationFor, useDutiableDiagnostics } from '../composables/useDutiableDiagnostics';
import { parseTimelineDate } from '../composables/useDutiableTimelineData';
import type { ParsedCadence, ParsedRow, StagedDates, TimelineDiagnostic } from '../types';

/**
 * The fixtures mirror DutiableDiagnosticsTest case for case. The two implementations are
 * deliberately separate — the server's is authoritative and gates every write, the
 * client's recomputes over staged state during a drag — so the shared cases are what stop
 * them drifting apart.
 */
function makeRow(overrides: Partial<ParsedRow> = {}): ParsedRow {
  const start = overrides.start_date ?? '2024-07-01';
  const end = overrides.end_date === undefined ? '2025-06-30' : overrides.end_date;

  return {
    id: 'row-1',
    group_key: 'user:u1',
    duty_id: 'duty-1',
    duty_name: 'Narys',
    institution_id: 'inst-1',
    institution_name: 'Parlamentas',
    holder_id: 'u1',
    holder_name: 'Vardas',
    holder_photo: null,
    tenant_id: null,
    tenant_shortname: null,
    via_dutiable_id: null,
    source: null,
    derived_ids: [],
    is_derived: false,
    editable: true,
    edit_url: '/mano/dutiables/row-1/edit',
    ...overrides,
    start_date: start,
    end_date: end,
    startDate: parseTimelineDate(start),
    endDate: end ? parseTimelineDate(end) : null,
  };
}

function makeCadence(overrides: Partial<ParsedCadence> = {}): ParsedCadence {
  const start = overrides.start_date ?? '2024-07-01';
  const end = overrides.end_date ?? '2025-06-30';

  return {
    id: 'cad-1',
    label: '2024–2025',
    institution_id: null,
    is_global: true,
    ...overrides,
    start_date: start,
    end_date: end,
    startDate: parseTimelineDate(start),
    endDate: parseTimelineDate(end),
  };
}

function analyze(
  rows: ParsedRow[],
  cadences: ParsedCadence[] = [makeCadence()],
  staged: Map<string, StagedDates> = new Map(),
  server: TimelineDiagnostic[] = [],
) {
  return useDutiableDiagnostics(ref(rows), ref(cadences), ref(staged), ref(server));
}

const codes = (findings: TimelineDiagnostic[]) => [...new Set(findings.map(finding => finding.code))].sort();

describe('useDutiableDiagnostics', () => {
  it('reports nothing for a cadence-aligned row', () => {
    expect(codes(analyze([makeRow()]).findings.value)).toEqual([]);
  });

  it('flags an end before the start', () => {
    const { findings } = analyze([makeRow({ start_date: '2025-06-30', end_date: '2024-07-01' })]);

    expect(codes(findings.value)).toContain('inverted');
  });

  it('flags two intersecting stints, with the date that separates them', () => {
    const { findings } = analyze([
      makeRow({ id: 'a', start_date: '2024-07-01', end_date: '2025-06-30' }),
      makeRow({ id: 'b', start_date: '2025-01-01', end_date: '2025-12-31' }),
    ]);

    const overlap = findings.value.find(finding => finding.code === 'overlap')!;

    expect(overlap.row_ids).toEqual(['a', 'b']);
    expect(overlap.detail).toMatchObject({ suggested_end: '2024-12-31' });
  });

  it('leaves genuinely separate stints alone', () => {
    const { findings } = analyze([
      makeRow({ id: 'a', start_date: '2020-07-01', end_date: '2021-06-30' }),
      makeRow({ id: 'b', start_date: '2024-07-01', end_date: '2025-06-30' }),
    ]);

    expect(codes(findings.value)).not.toContain('overlap');
  });

  it('flags one term ending the day the next begins', () => {
    const { findings } = analyze([
      makeRow({ id: 'a', start_date: '2023-07-01', end_date: '2024-07-01' }),
      makeRow({ id: 'b', start_date: '2024-07-01', end_date: '2025-06-30' }),
    ], [makeCadence(), makeCadence({ id: 'cad-0', start_date: '2023-07-01', end_date: '2024-06-30', label: '2023–2024' })]);

    const finding = findings.value.find(f => f.code === 'boundary_shared')!;

    expect(finding.detail).toMatchObject({ suggested_end: '2024-06-30' });
  });

  it('never collides an owning-tenant row with a cross-tenant representative row', () => {
    const { findings } = analyze([
      makeRow({ id: 'a' }),
      makeRow({ id: 'b', tenant_id: 7 }),
    ]);

    expect(codes(findings.value)).not.toContain('overlap');
  });

  it('flags an open-ended row whose own term is long over', () => {
    const { findings } = analyze([makeRow({ end_date: null })]);
    const finding = findings.value.find(f => f.code === 'open_ended_stale')!;

    expect(finding.detail).toMatchObject({ suggested_end: '2025-06-30' });
  });

  it('leaves an open-ended row inside the current term alone', () => {
    const now = new Date();
    const current = makeCadence({
      id: 'cad-now',
      start_date: `${now.getFullYear() - 1}-01-01`,
      end_date: `${now.getFullYear() + 1}-12-31`,
    });

    const { findings } = analyze([makeRow({ start_date: current.start_date, end_date: null })], [current]);

    expect(codes(findings.value)).not.toContain('open_ended_stale');
  });

  it('flags a derived row whose dates drifted from its source', () => {
    const { findings } = analyze([
      makeRow({ id: 'src' }),
      makeRow({ id: 'derived', via_dutiable_id: 'src', is_derived: true, start_date: '2024-09-01' }),
    ]);

    expect(codes(findings.value)).toContain('ex_officio_drift');
  });

  it('does not flag a derived row that still mirrors its source', () => {
    const { findings } = analyze([
      makeRow({ id: 'src' }),
      makeRow({ id: 'derived', via_dutiable_id: 'src', is_derived: true }),
    ]);

    expect(codes(findings.value)).not.toContain('ex_officio_drift');
  });

  it('treats a few weeks off the cadence as drift and a few months as deliberate', () => {
    expect(codes(analyze([makeRow({ start_date: '2024-06-01' })]).findings.value)).toContain('off_cadence');
    expect(codes(analyze([makeRow({ start_date: '2024-01-15' })]).findings.value)).not.toContain('off_cadence');
  });

  it('recomputes over staged state, which is what makes the panel live during a drag', () => {
    const rows = [makeRow({ start_date: '2024-06-01' })];
    const staged = ref(new Map<string, StagedDates>());
    const { findings } = useDutiableDiagnostics(ref(rows), ref([makeCadence()]), staged, ref([]));

    expect(codes(findings.value)).toContain('off_cadence');

    staged.value = new Map([['row-1', { start_date: '2024-07-01', end_date: '2025-06-30' }]]);

    expect(codes(findings.value)).not.toContain('off_cadence');
  });

  it('passes through the findings only the server can make', () => {
    const server: TimelineDiagnostic[] = [
      { code: 'orphan_derived_suspect', severity: 'info', row_ids: ['row-1'] },
      { code: 'understaffed', severity: 'info', row_ids: [] },
      { code: 'off_cadence', severity: 'info', row_ids: ['row-1'] },
    ];

    const { findings } = analyze([makeRow()], [makeCadence()], new Map(), server);

    // The server's own off_cadence is dropped: this side recomputes it over staged state.
    expect(codes(findings.value)).toEqual(['orphan_derived_suspect', 'understaffed']);
  });

  it('ranks a row by its worst finding through the counts', () => {
    const { counts } = analyze([
      makeRow({ id: 'a', start_date: '2025-06-30', end_date: '2024-07-01' }),
      makeRow({ id: 'b', start_date: '2024-06-01' }),
    ]);

    expect(counts.value.error).toBeGreaterThan(0);
    expect(counts.value.info).toBeGreaterThan(0);
  });
});

describe('fixOperationFor', () => {
  it('separates an overlap by ending the earlier row the day before', () => {
    expect(fixOperationFor({
      code: 'overlap', severity: 'error', row_ids: ['a', 'b'], detail: { suggested_end: '2024-12-31' },
    })).toEqual({ type: 'set_dates', row_ids: ['a'], end_date: '2024-12-31' });
  });

  it('closes a stale open-ended row at its own term end', () => {
    expect(fixOperationFor({
      code: 'open_ended_stale', severity: 'warning', row_ids: ['a'], detail: { suggested_end: '2025-06-30' },
    })).toEqual({ type: 'close_open_ended', row_ids: ['a'], end_date: '2025-06-30' });
  });

  it('offers no automatic fix where guessing would take real access away', () => {
    expect(fixOperationFor({ code: 'orphan_derived_suspect', severity: 'info', row_ids: ['a'] })).toBeNull();
    expect(fixOperationFor({ code: 'ex_officio_drift', severity: 'warning', row_ids: ['a'] })).toBeNull();
    expect(fixOperationFor({ code: 'understaffed', severity: 'info', row_ids: [] })).toBeNull();
  });
});
