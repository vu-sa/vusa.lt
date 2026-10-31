import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import DutiableTimelineSelectionPanel from '../DutiableTimelineSelectionPanel.vue';
import type { ParsedCadence, ParsedRow, StagedDates } from '../types';

import { commonStubs } from '@/tests/stubs';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { globalProgress } from '@/Composables/useTutorialProgress';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

function makeRow(overrides: Partial<ParsedRow> = {}): ParsedRow {
  return {
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
    extras: null,
    start_date: '2025-05-18',
    end_date: '2026-05-17',
    via_dutiable_id: null,
    source: null,
    derived_ids: [],
    is_derived: false,
    editable: true,
    edit_url: '/mano/dutiables/row-1/edit',
    startDate: new Date(2025, 4, 18, 12),
    endDate: new Date(2026, 4, 17, 12),
    ...overrides,
  };
}

const cadence: ParsedCadence = {
  id: 'cad-1',
  label: '2025–2026',
  start_date: '2025-07-01',
  end_date: '2026-06-30',
  institution_id: null,
  is_global: true,
  startDate: new Date(2025, 6, 1, 12),
  endDate: new Date(2026, 5, 30, 12),
};

/**
 * The calendar popover is stubbed (reka-ui positions it with Popper, which jsdom cannot
 * do). The literal YYYY-MM-DD field is what these tests drive — and the same field an
 * admin uses when a date has to be exact, so it is the honest path to cover.
 */
function mountPanel(
  row: ParsedRow | null,
  staged: Map<string, StagedDates> = new Map(),
  selectedRows: ParsedRow[] = row ? [row] : [],
) {
  return mount(DutiableTimelineSelectionPanel, {
    props: { row, cadences: [cadence], staged, selectedRows },
    global: { stubs: { ...commonStubs, Popover: true, AlertDialog: true } },
  });
}

describe('DutiableTimelineSelectionPanel', () => {
  it('prompts for a selection when nothing is active', () => {
    expect(mountPanel(null).text()).toContain('dutiables.timeline.inspector.empty');
  });

  it('offers exactly one date control per edge', () => {
    const wrapper = mountPanel(makeRow());

    expect(wrapper.findAll('[data-slot="timeline-date-input"]')).toHaveLength(2);
  });

  it('stages a typed start date once it is complete', async () => {
    const wrapper = mountPanel(makeRow());
    const input = wrapper.find('#selection-start');

    await input.setValue('2025-07-01');
    await input.trigger('change');

    expect(wrapper.emitted('stage')?.[0]).toEqual([
      'row-1', { start_date: '2025-07-01', end_date: '2026-05-17' },
    ]);
  });

  it('ignores a half-typed date rather than staging a nonsense year', async () => {
    const wrapper = mountPanel(makeRow());
    const input = wrapper.find('#selection-start');

    await input.setValue('2025-0');
    await input.trigger('change');

    expect(wrapper.emitted('stage')).toBeUndefined();
  });

  it('clearing the end field stages an open-ended period', async () => {
    const wrapper = mountPanel(makeRow());
    const input = wrapper.find('#selection-end');

    await input.setValue('');
    await input.trigger('change');

    expect(wrapper.emitted('stage')?.[0]).toEqual([
      'row-1', { start_date: '2025-05-18', end_date: null },
    ]);
  });

  it('shows the staged value, not the stored one, once an edit is pending', () => {
    const staged = new Map([['row-1', { start_date: '2025-07-01', end_date: '2026-06-30' }]]);
    const wrapper = mountPanel(makeRow(), staged);

    expect((wrapper.find('#selection-start').element as HTMLInputElement).value).toBe('2025-07-01');
  });

  /** Duration is already in the label column and drift in the suggestions; the panel keeps to dates and actions. */
  it('leaves duration and cadence drift to the chart and the suggestions', () => {
    const text = mountPanel(makeRow()).text();

    expect(text).not.toContain('dutiables.timeline.duration.label');
    expect(text).not.toContain('dutiables.timeline.inspector.off_by');
  });

  it('locks a derived row and offers its source instead', async () => {
    const wrapper = mountPanel(makeRow({
      is_derived: true,
      via_dutiable_id: 'row-0',
      editable: false,
      source: { id: 'row-0', duty_name: 'Pirmininkas' },
    }));

    expect((wrapper.find('#selection-start').element as HTMLInputElement).disabled).toBe(true);

    await wrapper.findAll('button').find(button => button.text().includes('select_source'))!.trigger('click');

    expect(wrapper.emitted('select-source')?.[0]).toEqual(['row-0']);
  });

  it('refuses to stage anything for a row the user may not manage', async () => {
    const wrapper = mountPanel(makeRow({ editable: false }));

    await wrapper.find('#selection-start').setValue('2025-07-01');
    await wrapper.find('#selection-start').trigger('change');

    expect(wrapper.emitted('stage')).toBeUndefined();
    expect(wrapper.text()).toContain('dutiables.timeline.inspector.not_editable');
  });

  it('asks the editor to align, rather than picking a cadence itself', async () => {
    const wrapper = mountPanel(makeRow());

    await wrapper.findAll('button').find(button => button.text().includes('actions.align'))!.trigger('click');

    expect(wrapper.emitted('align')).toHaveLength(1);
  });
});

describe('DutiableTimelineSelectionPanel with several rows selected', () => {
  const first = makeRow({ id: 'row-1' });
  const sameHolder = makeRow({ id: 'row-2', start_date: '2026-07-01', end_date: '2027-06-30' });
  const otherHolder = makeRow({ id: 'row-3', holder_id: 'u2', holder_name: 'Kitas Žmogus' });

  it('applies typed dates across the whole selection', async () => {
    const wrapper = mountPanel(first, new Map(), [first, otherHolder]);

    const input = wrapper.find('#bulk-start');
    await input.setValue('2026-07-01');
    await input.trigger('change');

    await wrapper.findAll('button').find(button => button.text().includes('apply_dates'))!.trigger('click');

    expect(wrapper.emitted('set-dates')?.[0]).toEqual([
      { start_date: '2026-07-01', end_date: null },
    ]);
  });

  it('will not apply until at least one edge is typed', () => {
    const wrapper = mountPanel(first, new Map(), [first, otherHolder]);

    const apply = wrapper.findAll('button').find(button => button.text().includes('apply_dates'))!;

    expect(apply.attributes('disabled')).toBeDefined();
  });

  /**
   * Same person, same duty, same tenant — the rule MergeDutiables enforces server-side.
   * Asserted through the absence of the "why not" hint, because the button itself lives
   * inside a stubbed AlertDialog trigger.
   */
  it('offers a merge for two stints of one holder', () => {
    const wrapper = mountPanel(first, new Map(), [first, sameHolder]);

    expect(wrapper.text()).not.toContain('dutiables.timeline.actions.merge_hint');
  });

  it('refuses to offer a merge across different holders', () => {
    const wrapper = mountPanel(first, new Map(), [first, otherHolder]);

    expect(wrapper.text()).toContain('dutiables.timeline.actions.merge_hint');
  });

  it('refuses to offer a merge when a row is not editable', () => {
    const locked = makeRow({ id: 'row-2', editable: false });
    const wrapper = mountPanel(first, new Map(), [first, locked]);

    expect(wrapper.text()).toContain('dutiables.timeline.actions.merge_hint');
  });
});

describe('DutiableTimelineSelectionPanel extras', () => {
  it('says nothing when a row is only a period', () => {
    expect(mountPanel(makeRow()).text()).not.toContain('dutiables.timeline.extras.email');
  });

  it('spells out the details a merge or delete would take with it', () => {
    const wrapper = mountPanel(makeRow({
      extras: { email: 'pirmininkas@vusa.lt', study_program: 'Programų sistemos' },
    }));

    expect(wrapper.text()).toContain('pirmininkas@vusa.lt');
    expect(wrapper.text()).toContain('Programų sistemos');
  });
});

function mountReview(entry: ParsedRow) {
  return mount(DutiableTimelineSelectionPanel, {
    props: { row: entry, cadences: [cadence], staged: new Map(), selectedRows: [entry] },
    global: { stubs: { ...commonStubs, Popover: true,
      SpotlightPopover: { template: '<div><slot /></div>' },
      AlertDialog: { template: '<div><slot /></div>' },
      AlertDialogTrigger: { template: '<div><slot /></div>' },
      AlertDialogContent: { template: '<div><slot /></div>' },
      AlertDialogHeader: { template: '<div><slot /></div>' },
      AlertDialogTitle: { template: '<div><slot /></div>' },
      AlertDialogDescription: { template: '<div><slot /></div>' },
      AlertDialogFooter: { template: '<div><slot /></div>' },
      AlertDialogCancel: { template: '<div><slot /></div>' },
      AlertDialogAction: { template: '<button><slot /></button>' },
    } },
  });
}

it('offers independent and source-linked review choices only for editable root terms', () => {
  const row = makeRow({ review_sources: [{ id: 'source-1', name: 'Pirmininkas', start_date: '2025-07-01', end_date: null }] });
  const wrapper = mountReview(row);
  expect(wrapper.text()).toContain('dutiables.timeline.review.keep');
  expect(wrapper.text()).toContain('dutiables.timeline.review.convert');
  expect(mountReview({ ...row, editable: false }).text()).not.toContain('dutiables.timeline.review.keep');
  expect(mountReview({ ...row, is_derived: true }).text()).not.toContain('dutiables.timeline.review.keep');

  const withoutSource = mountReview({ ...row, review_sources: [] }).text();
  expect(withoutSource).not.toContain('dutiables.timeline.review.explanation');
  expect(withoutSource).not.toContain('dutiables.timeline.review.keep');

  const confirmed = mountReview({ ...row, assignment_origin: 'manual' }).text();
  expect(confirmed).not.toContain('dutiables.timeline.review.keep');
  expect(confirmed).toContain('dutiables.timeline.review.convert');
});

it.each([
  { is_derived: true, detachable: true, shown: true },
  { is_derived: true, detachable: false, shown: false },
  { is_derived: true, detachable: undefined, shown: false },
  { is_derived: false, detachable: true, shown: false },
])('shows detach only for a detachable derived row: $is_derived / $detachable', ({ is_derived, detachable, shown }) => {
  const wrapper = mountReview(makeRow({ is_derived, detachable, editable: false }));
  const detach = wrapper.findAll('button').filter(button => button.text() === 'dutiables.timeline.review.detach');

  expect(detach).toHaveLength(shown ? 1 : 0);
  if (shown) {
    expect(wrapper.text()).toContain('dutiables.timeline.review.detach_explanation');
    expect(wrapper.text()).not.toContain('dutiables.timeline.inspector.ex_officio_managed');
  }
});

it('confirms detach through the review route and emits reviewed on success without dismissing the spotlight', async () => {
  vi.mocked(router.post).mockClear();
  const spotlightBefore = { ...globalProgress.value };
  const row = makeRow({ is_derived: true, detachable: true, editable: false, via_dutiable_id: 'source-1' });
  const wrapper = mountReview(row);

  await wrapper.findAll('button').find(button => button.text() === 'dutiables.timeline.review.detach')!.trigger('click');
  expect(router.post).not.toHaveBeenCalled();

  await wrapper.findAll('button').find(button => button.text() === 'dutiables.timeline.review.confirm')!.trigger('click');

  expect(router.post).toHaveBeenCalledExactlyOnceWith(route('dutiables.reviewExOfficio', row.id), {
    decision: 'detach', source_id: null, acknowledge: true, acknowledge_access_change: false,
  }, expect.objectContaining({ preserveScroll: true, onSuccess: expect.any(Function) }));
  expect(globalProgress.value).toEqual(spotlightBefore);
  expect(wrapper.emitted('reviewed')).toBeUndefined();

  const options = vi.mocked(router.post).mock.calls[0][2];
  options?.onSuccess?.(createMockPage());

  expect(wrapper.emitted('reviewed')).toHaveLength(1);
});
