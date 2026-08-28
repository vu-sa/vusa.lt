import { describe, expect, it, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { ref } from 'vue';

import { commonStubs } from '@/tests/stubs';

// The toolbar's reka-ui Slider measures itself on mount; jsdom ships no ResizeObserver.
// Polyfilled locally rather than in the shared setup so no other suite changes behaviour.
class ResizeObserverStub {
  observe() {}
  unobserve() {}
  disconnect() {}
}
vi.stubGlobal('ResizeObserver', ResizeObserverStub);

import DutiableTimelineEditor from '../DutiableTimelineEditor.vue';
import type { TimelinePayload, TimelinePlanPayload } from '../types';

const payload = ref<TimelinePayload | null>(null);
const isFetching = ref(false);
const previewPlan = ref<TimelinePlanPayload | null>(null);
const previewExecute = vi.fn();
/** The mutation body is a computed the composable owns; capturing it is how the tests
 *  read the operation list that would be posted. */
let previewBody: { value: { operations: unknown[] } } | null = null;

vi.mock('@/Composables/useApi', () => ({
  useApi: vi.fn(() => ({
    data: payload,
    error: ref(null),
    isFetching,
    execute: vi.fn(),
  })),
  useApiMutation: vi.fn((_url: unknown, _method: unknown, body: never) => {
    previewBody = body;

    return {
      data: previewPlan,
      error: ref(null),
      isFetching: ref(false),
      execute: previewExecute,
    };
  }),
}));

function makePayload(overrides: Partial<TimelinePayload> = {}): TimelinePayload {
  return {
    scope: { type: 'duty', id: 'duty-1', label: 'Parlamento narys', sublabel: 'Parlamentas' },
    groups: [{ key: 'user:u1', kind: 'user', label: 'Vardas Pavardė' }],
    rows: [{
      id: 'row-1',
      group_key: 'user:u1',
      duty_id: 'duty-1',
      duty_name: 'Parlamento narys',
      institution_id: 'inst-1',
      institution_name: 'Parlamentas',
      holder_id: 'u1',
      holder_name: 'Vardas Pavardė',
      holder_photo: null,
      tenant_id: null,
      tenant_shortname: null,
      cadence_id: 'cad-1',
      cadence_ids: ['cad-1'],
      start_date: '2025-05-18',
      end_date: '2026-06-30',
      via_dutiable_id: null,
      extras: null,
      source: null,
      derived_ids: [],
      is_derived: false,
      editable: true,
      edit_url: '/mano/dutiables/row-1/edit',
    }],
    cadences: [{
      id: 'cad-1',
      label: '2025–2026',
      start_date: '2025-07-01',
      end_date: '2026-06-30',
      institution_id: null,
      is_global: true,
    }],
    cadence_defaults: { start_month_day: '07-01', end_month_day: '06-30' },
    diagnostics: [],
    meta: { row_count: 1, truncated: false, max_rows: 1500 },
    ...overrides,
  };
}

/**
 * The chart itself is stubbed: it renders imperatively with d3 into an SVG whose
 * geometry only exists once a real layout engine has given the container a width, so
 * bar positions, the month grid and the cadence bands are not observable in jsdom.
 * These tests cover the wiring around it — what the editor passes down and what it
 * does with what comes back. The geometry-free parts (lane stacking, date parsing,
 * month-boundary detection) are covered directly in useDutiableLayout.test.ts and
 * timelineDates.test.ts.
 */
const stubs = {
  ...commonStubs,
  DutiableGantt: {
    name: 'DutiableGantt',
    props: ['layoutRows', 'rows', 'cadences', 'bandCadences', 'highlightedCadenceIds', 'domain', 'totalHeight', 'collapsed', 'groupSummaries', 'allCollapsed', 'sortMode', 'sortable', 'monthWidthPx', 'selectedIds', 'staged'],
    template: '<div class="gantt-stub" @click="$emit(\'select\', layoutRows.find(l => l.row)?.row, { ctrlKey: false, metaKey: false })" />',
    emits: ['select', 'toggle-group', 'stage'],
  },
};

function mountEditor() {
  return mount(DutiableTimelineEditor, {
    props: { scopeType: 'duty' as const, scopeId: 'duty-1' },
    global: { stubs },
  });
}

describe('DutiableTimelineEditor', () => {
  beforeEach(() => {
    payload.value = makePayload();
    isFetching.value = false;
    previewPlan.value = null;
    previewExecute.mockClear();
  });

  it('passes a lane per group and row to the chart', () => {
    const wrapper = mountEditor();
    const gantt = wrapper.findComponent({ name: 'DutiableGantt' });

    expect(gantt.props('layoutRows')).toHaveLength(2);
    expect(gantt.props('cadences')).toHaveLength(1);
  });

  it('parses cadence dates into Date objects before handing them down', () => {
    const wrapper = mountEditor();
    const cadences = wrapper.findComponent({ name: 'DutiableGantt' }).props('cadences') as Array<{ startDate: Date }>;

    expect(cadences[0].startDate).toBeInstanceOf(Date);
    expect(cadences[0].startDate.getDate()).toBe(1);
  });

  it('draws only the institution own ladder when it overrides the global one', () => {
    payload.value = makePayload({
      scope: { type: 'institution', id: 'inst-1', label: 'Parlamentas', institution_id: 'inst-1' },
      cadences: [
        {
          id: 'global-1',
          label: '2025–2026',
          start_date: '2025-07-01',
          end_date: '2026-06-30',
          institution_id: null,
          is_global: true,
        },
        {
          id: 'parl-1',
          label: '2025–2026',
          start_date: '2025-05-18',
          end_date: '2026-05-17',
          institution_id: 'inst-1',
          is_global: false,
        },
      ],
    });

    const gantt = mountEditor().findComponent({ name: 'DutiableGantt' });

    // Matching still needs both ladders; only one of them is ever painted.
    expect(gantt.props('cadences')).toHaveLength(2);
    expect((gantt.props('bandCadences') as Array<{ id: string }>).map(c => c.id)).toEqual(['parl-1']);
  });

  it('offers the study-programme sort only where a programme is recorded', () => {
    expect(mountEditor().findComponent({ name: 'DutiableGantt' }).props('sortable')).toBe(false);

    const base = makePayload();
    payload.value = {
      ...base,
      rows: [{ ...base.rows[0], extras: { study_program: 'Programų sistemos' } }],
    };

    expect(mountEditor().findComponent({ name: 'DutiableGantt' }).props('sortable')).toBe(true);
  });

  it('shows an empty state rather than an empty chart when nothing matches', () => {
    payload.value = makePayload({ groups: [], rows: [] });

    const wrapper = mountEditor();

    expect(wrapper.findComponent({ name: 'DutiableGantt' }).exists()).toBe(false);
    expect(wrapper.text()).toContain('dutiables.timeline.empty.title');
  });

  it('selects a row when the chart emits one, and deselects on a second click', async () => {
    const wrapper = mountEditor();
    const gantt = wrapper.findComponent({ name: 'DutiableGantt' });

    await gantt.trigger('click');
    expect([...(gantt.props('selectedIds') as Set<string>)]).toEqual(['row-1']);

    await gantt.trigger('click');
    expect((gantt.props('selectedIds') as Set<string>).size).toBe(0);
  });

  it('stages what the chart drags, and drops the dirty bar once reverted', async () => {
    const wrapper = mountEditor();
    const gantt = wrapper.findComponent({ name: 'DutiableGantt' });

    await gantt.vm.$emit('stage', [{ rowId: 'row-1', dates: { start_date: '2025-07-18', end_date: '2026-06-30' } }]);

    expect((gantt.props('staged') as Map<string, unknown>).get('row-1'))
      .toEqual({ start_date: '2025-07-18', end_date: '2026-06-30' });
    expect(wrapper.text()).toContain('dutiables.timeline.staging.dirty_count');

    await wrapper.findComponent({ name: 'DutiableTimelineDirtyBar' }).vm.$emit('discard');

    expect((gantt.props('staged') as Map<string, unknown>).size).toBe(0);
  });

  it('does not stage a drag that lands back on the persisted dates', async () => {
    const wrapper = mountEditor();
    const gantt = wrapper.findComponent({ name: 'DutiableGantt' });

    await gantt.vm.$emit('stage', [{ rowId: 'row-1', dates: { start_date: '2025-05-18', end_date: '2026-06-30' } }]);

    expect((gantt.props('staged') as Map<string, unknown>).size).toBe(0);
    expect(wrapper.text()).not.toContain('dutiables.timeline.staging.dirty_count');
  });

  it('asks the server for a dry run before saving', async () => {
    const wrapper = mountEditor();
    const gantt = wrapper.findComponent({ name: 'DutiableGantt' });

    await gantt.vm.$emit('stage', [{ rowId: 'row-1', dates: { start_date: '2025-07-01', end_date: null } }]);
    await wrapper.findComponent({ name: 'DutiableTimelineDirtyBar' }).vm.$emit('preview');

    expect(previewExecute).toHaveBeenCalled();
  });

  /** No `cadence_id`: the planner resolves each edge's own term. */
  it('scopes an align to the selection and names no cadence', async () => {
    const wrapper = mountEditor();
    await wrapper.findComponent({ name: 'DutiableGantt' }).trigger('click');

    await wrapper.findComponent({ name: 'DutiableTimelineSelectionPanel' }).vm.$emit('align');

    expect(previewExecute).toHaveBeenCalled();
    expect(previewBody!.value.operations).toEqual([
      { type: 'align_to_cadence', row_ids: ['row-1'], edges: 'both' },
    ]);
  });

  it('keeps an align off rows the user may not manage', async () => {
    payload.value = makePayload({
      rows: [{ ...makePayload().rows[0], editable: false }],
    });

    const wrapper = mountEditor();
    await wrapper.findComponent({ name: 'DutiableGantt' }).trigger('click');
    await wrapper.findComponent({ name: 'DutiableTimelineSelectionPanel' }).vm.$emit('align');

    expect(previewExecute).not.toHaveBeenCalled();
  });

  /**
   * Three seats: one in each term, and one re-elected across both. Filtering to either term
   * has to keep the spanning row — that is what the single-cadence equality test got wrong.
   */
  function multiCadencePayload() {
    const base = makePayload().rows[0];

    return makePayload({
      groups: [
        { key: 'user:u1', kind: 'user', label: 'Vardas Pavardė' },
        { key: 'user:u2', kind: 'user', label: 'Kitas Žmogus' },
        { key: 'user:u3', kind: 'user', label: 'Perrinktas Žmogus' },
      ],
      rows: [
        { ...base },
        { ...base, id: 'row-2', group_key: 'user:u2', holder_id: 'u2', cadence_id: 'cad-2', cadence_ids: ['cad-2'] },
        {
          ...base,
          id: 'row-3',
          group_key: 'user:u3',
          holder_id: 'u3',
          cadence_id: 'cad-2',
          cadence_ids: ['cad-1', 'cad-2'],
        },
      ],
    });
  }

  it('narrows the chart to the filtered cadence, keeping rows that span it', async () => {
    payload.value = multiCadencePayload();

    const wrapper = mountEditor();
    expect(wrapper.findComponent({ name: 'DutiableGantt' }).props('rows')).toHaveLength(3);

    await wrapper.findComponent({ name: 'DutiableTimelineToolbar' }).vm.$emit('update:cadenceIds', ['cad-1']);

    const rows = wrapper.findComponent({ name: 'DutiableGantt' }).props('rows') as Array<{ id: string }>;
    expect(rows.map(row => row.id)).toEqual(['row-1', 'row-3']);
  });

  it('counts a spanning row under every term it covers', () => {
    payload.value = multiCadencePayload();

    const options = mountEditor()
      .findComponent({ name: 'DutiableTimelineToolbar' })
      .props('cadenceOptions') as Array<{ value: string; count: number }>;

    expect(options.find(option => option.value === 'cad-1')?.count).toBe(2);
    expect(options.find(option => option.value === 'cad-2')?.count).toBe(2);
  });

  it('marks the filtered terms for the chart to highlight', async () => {
    payload.value = multiCadencePayload();

    const wrapper = mountEditor();
    expect(wrapper.findComponent({ name: 'DutiableGantt' }).props('highlightedCadenceIds')).toEqual(new Set());

    await wrapper.findComponent({ name: 'DutiableTimelineToolbar' }).vm.$emit('update:cadenceIds', ['cad-2']);

    expect(wrapper.findComponent({ name: 'DutiableGantt' }).props('highlightedCadenceIds'))
      .toEqual(new Set(['cad-2']));
  });

  it('offers no unit filter when nothing records a cross-tenant unit', () => {
    payload.value = makePayload();

    expect(mountEditor().findComponent({ name: 'DutiableTimelineToolbar' }).props('tenantOptions')).toEqual([]);
  });

  it('warns when the payload was truncated', () => {
    payload.value = makePayload({ meta: { row_count: 1500, truncated: true, max_rows: 1500 } });

    expect(mountEditor().text()).toContain('dutiables.timeline.truncated');
  });
});
