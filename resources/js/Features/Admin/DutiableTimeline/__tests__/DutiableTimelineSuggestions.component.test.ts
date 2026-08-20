import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';

import DutiableTimelineSuggestions from '../DutiableTimelineSuggestions.vue';
import type { ParsedRow, TimelineDiagnostic } from '../types';

function makeRow(overrides: Partial<ParsedRow> = {}): ParsedRow {
  return {
    id: 'row-1',
    group_key: 'duty:duty-1',
    duty_id: 'duty-1',
    duty_name: 'Pirmininkas',
    institution_id: 'inst-1',
    institution_name: 'Parlamentas',
    holder_id: 'u1',
    holder_name: 'Vardas Pavardė',
    holder_photo: null,
    tenant_id: null,
    tenant_shortname: null,
    cadence_id: 'cad-1',
    start_date: '2024-06-01',
    end_date: '2025-06-30',
    via_dutiable_id: null,
    source: null,
    derived_ids: [],
    is_derived: false,
    editable: true,
    edit_url: '/mano/dutiables/row-1/edit',
    startDate: new Date(2024, 5, 1, 12),
    endDate: new Date(2025, 5, 30, 12),
    ...overrides,
  };
}

function mountPanel(findings: TimelineDiagnostic[], rows = [makeRow()]) {
  return mount(DutiableTimelineSuggestions, {
    props: {
      findings,
      counts: {
        error: findings.filter(f => f.severity === 'error').length,
        warning: findings.filter(f => f.severity === 'warning').length,
        info: findings.filter(f => f.severity === 'info').length,
      },
      rows,
    },
    global: { stubs: commonStubs },
  });
}

const overlap: TimelineDiagnostic = {
  code: 'overlap',
  severity: 'error',
  row_ids: ['row-1'],
  duty_id: 'duty-1',
  detail: { suggested_end: '2025-05-17' },
};

describe('DutiableTimelineSuggestions', () => {
  it('says nothing was found when there is nothing to fix', () => {
    expect(mountPanel([]).text()).toContain('dutiables.timeline.diagnostics.empty');
  });

  it('names the row a finding is about, not just the code', () => {
    expect(mountPanel([overlap]).text()).toContain('Vardas Pavardė');
  });

  /** The point of the rewrite: a bare code never said what "Fix" would write. */
  it('shows the dates the fix would write', () => {
    expect(mountPanel([overlap]).text()).toContain('dutiables.timeline.diagnostics.detail.end_move');
  });

  it('reports how many places are actually filled', () => {
    const wrapper = mountPanel([{
      code: 'understaffed',
      severity: 'info',
      row_ids: [],
      duty_id: 'duty-1',
      detail: { active: 2, places_to_occupy: 3 },
    }]);

    expect(wrapper.text()).toContain('dutiables.timeline.diagnostics.detail.understaffed');
  });

  it('pre-checks errors and applies them as one batch', async () => {
    const wrapper = mountPanel([overlap]);

    await wrapper.findAll('button').find(button => button.text().includes('apply_selected'))!.trigger('click');

    expect(wrapper.emitted('apply')?.[0]?.[0]).toEqual([
      { type: 'set_dates', row_ids: ['row-1'], end_date: '2025-05-17' },
    ]);
  });

  it('leaves an informational finding unchecked, so applying does nothing by default', async () => {
    const wrapper = mountPanel([{
      code: 'off_cadence',
      severity: 'info',
      row_ids: ['row-1'],
      duty_id: 'duty-1',
      detail: { cadence_id: 'cad-1', drift_days: { start: 30 } },
    }]);

    const apply = wrapper.findAll('button').find(button => button.text().includes('apply_selected'))!;

    expect(apply.attributes('disabled')).toBeDefined();
  });

  it('batches every checked finding into one operation list', async () => {
    const second: TimelineDiagnostic = {
      code: 'inverted',
      severity: 'error',
      row_ids: ['row-2'],
      duty_id: 'duty-1',
    };

    const wrapper = mountPanel([overlap, second], [makeRow(), makeRow({ id: 'row-2' })]);

    await wrapper.findAll('button').find(button => button.text().includes('apply_selected'))!.trigger('click');

    expect(wrapper.emitted('apply')?.[0]?.[0]).toHaveLength(2);
  });

  it('focuses the rows a finding names when its label is clicked', async () => {
    const wrapper = mountPanel([overlap]);

    await wrapper.findAll('button').find(button => button.text().includes('overlap'))!.trigger('click');

    expect(wrapper.emitted('focus')?.[0]).toEqual([['row-1']]);
  });
});
