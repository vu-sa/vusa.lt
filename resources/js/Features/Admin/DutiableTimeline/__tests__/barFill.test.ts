import { describe, expect, it } from 'vitest';

import { barFill } from '../renderers/renderDutiableBars';
import { getTimelineColors } from '../timelineColors';
import type { ParsedRow } from '../types';

const colors = getTimelineColors(false);

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
    extras: null,
    start_date: '2018-07-01',
    end_date: '2019-06-30',
    via_dutiable_id: null,
    source: null,
    derived_ids: [],
    is_derived: false,
    editable: true,
    edit_url: '/mano/dutiables/row-1/edit',
    startDate: new Date(2018, 6, 1, 12),
    endDate: new Date(2019, 5, 30, 12),
    ...overrides,
  };
}

const open = { end_date: null, endDate: null } as Partial<ParsedRow>;

describe('barFill', () => {
  it('paints a currently held seat as active', () => {
    expect(barFill(makeRow(open), colors, undefined)).toBe(colors.active);
  });

  it('mutes a seat that has already ended', () => {
    expect(barFill(makeRow(), colors, undefined)).toBe(colors.former);
  });

  it('marks a live ex-officio seat', () => {
    expect(barFill(makeRow({ ...open, is_derived: true }), colors, undefined)).toBe(colors.derived);
  });

  /**
   * The point of the rule: amber says "live, and mirrored from elsewhere". On a seat that
   * ended years ago it made history shout louder than the present, so ended wins.
   */
  it('mutes an ex-officio seat that has ended, rather than keeping it amber', () => {
    expect(barFill(makeRow({ is_derived: true }), colors, undefined)).toBe(colors.former);
  });

  it('an unsaved edit outranks every status, ended or not', () => {
    const staged = { start_date: '2018-07-01', end_date: '2019-06-30' };

    expect(barFill(makeRow({ is_derived: true }), colors, staged)).toBe(colors.staged);
    expect(barFill(makeRow(), colors, { ...staged, projected: true })).toBe(colors.projected);
  });
});
