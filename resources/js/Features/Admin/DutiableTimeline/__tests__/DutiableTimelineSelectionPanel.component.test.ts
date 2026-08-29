import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';

import DutiableTimelineSelectionPanel from '../DutiableTimelineSelectionPanel.vue';
import type { ParsedCadence, ParsedRow, StagedDates } from '../types';

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

  it('measures drift against the cadence the staged start falls in', () => {
    const aligned = mountPanel(makeRow(), new Map([['row-1', { start_date: '2025-07-01', end_date: '2026-06-30' }]]));
    expect(aligned.text()).toContain('dutiables.timeline.inspector.aligned');

    expect(mountPanel(makeRow()).text()).toContain('dutiables.timeline.inspector.off_by');
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
