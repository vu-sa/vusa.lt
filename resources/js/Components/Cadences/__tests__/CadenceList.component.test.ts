import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';

import { commonStubs } from '@/tests/stubs';

import CadenceList from '../CadenceList.vue';
import type { CadenceRow } from '../CadenceList.vue';

function makeRow(overrides: Partial<CadenceRow> = {}): CadenceRow {
  return {
    id: 'cadence-1',
    institution_id: 'inst-1',
    start_date: '2025-05-18',
    end_date: '2026-05-17',
    label: '2025—2026',
    ...overrides,
  };
}

function mountList(cadences: CadenceRow[]) {
  return mount(CadenceList, {
    props: { cadences, emptyMessage: 'empty' },
    global: { stubs: commonStubs },
  });
}

describe('CadenceList', () => {
  it('names the institution of a boundary taken from another body’s sitting', () => {
    const wrapper = mountList([makeRow({
      start_meeting: {
        id: 'meeting-1',
        title: 'Konferencija',
        start_time: '2025-05-18T10:00:00+03:00',
        institution_id: 'inst-2',
        institution_name: 'VU SA MIF',
      },
    })]);

    expect(wrapper.text()).toContain('Konferencija');
    expect(wrapper.text()).toContain('VU SA MIF');
  });

  it('leaves the institution unnamed when the sitting is the term owner’s own', () => {
    const wrapper = mountList([makeRow({
      start_meeting: {
        id: 'meeting-1',
        title: 'Konferencija',
        start_time: '2025-05-18T10:00:00+03:00',
        institution_id: 'inst-1',
        institution_name: 'VU SA MIF',
      },
    })]);

    expect(wrapper.text()).toContain('Konferencija');
    expect(wrapper.text()).not.toContain('VU SA MIF');
  });
});
