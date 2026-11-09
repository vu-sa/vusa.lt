import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import type { HandoverNoteData, WritableHandoverDuty } from '@/Types/learning';
import HandoverNotes from '../HandoverNotes.vue';

const note: HandoverNoteData = {
  id: 'note', dutyId: 'duty', duty: 'SPK representative', seatDutyId: 'duty', seatTitle: 'SPK representative',
  cadenceId: 'cadence-2025', cadenceLabel: '2025–2026', termStart: '2025-07-01', author: { id: 'author', name: 'Rūta' },
  situation: '<script>alert(1)</script>\nSecond line', open_issues: 'Discuss the proposal', people: null, resources: null, advice: null,
  createdAt: '2025-11-01T12:00:00+02:00', updatedAt: '2026-11-02T12:00:00+02:00',
};

describe('HandoverNotes', () => {
  it('shows labelled plain text sections, cadence, author and date while hiding empty fields', () => {
    const wrapper = mount(HandoverNotes, { props: { notes: [note] } });
    expect(wrapper.get('[data-slot="handover-entry"] h3').text()).toBe('2025–2026');
    expect(wrapper.text()).toContain('SPK representative');
    expect(wrapper.text()).toContain('Rūta');
    expect(wrapper.get('time').attributes('datetime')).toBe('2026-11-02T12:00:00+02:00');
    expect(wrapper.findAll('dt')).toHaveLength(2);
    expect(wrapper.findAll('dd')[0]?.text()).toBe('<script>alert(1)</script>\nSecond line');
    expect(wrapper.findAll('dd')[0]?.classes()).toContain('whitespace-pre-wrap');
    expect(wrapper.find('script').exists()).toBe(false);
    expect(wrapper.find('textarea').exists()).toBe(false);
    wrapper.unmount();
  });

  it('orders by cadence rather than edit time and groups predecessor entries under the current seat', () => {
    const current = { ...note, id: 'new', cadenceId: 'cadence-2026', cadenceLabel: '2026–2027', termStart: '2026-07-01', updatedAt: '2026-10-01T12:00:00+02:00' };
    const previous = { ...note, dutyId: 'previous-duty', duty: 'Former seat' };
    const wrapper = mount(HandoverNotes, { props: { notes: [previous, current] } });
    expect(wrapper.findAll('[data-slot="handover-timeline"]')).toHaveLength(1);
    expect(wrapper.findAll('[data-slot="handover-entry"] h3').map(heading => heading.text())).toEqual(['2026–2027', '2025–2026']);
    expect(wrapper.text()).toContain('Former seat');
    expect(wrapper.find('textarea').exists()).toBe(false);
    wrapper.unmount();
  });

  it('edits only the own current entry inline without duplicating its read view', () => {
    const own = { ...note, id: 'own', cadenceId: 'cadence-2026', cadenceLabel: '2026–2027', termStart: '2026-07-01' };
    const duty: WritableHandoverDuty = {
      dutyId: 'duty', title: 'SPK representative', cadenceId: own.cadenceId, cadenceLabel: own.cadenceLabel,
      termStart: '2026-07-01', author: own.author, note: own, notes: [own, note],
    };
    const wrapper = mount(HandoverNotes, { props: { notes: [note], writable: [duty] } });
    expect(wrapper.findAll('[data-slot="handover-entry"]')).toHaveLength(2);
    const entries = wrapper.findAll('[data-slot="handover-entry"]');
    expect(entries[0]?.findAll('textarea')).toHaveLength(5);
    expect(entries[0]?.find('[data-slot="handover-sections"]').exists()).toBe(false);
    expect(entries[1]?.find('textarea').exists()).toBe(false);
    wrapper.unmount();
  });

  it('offers a five-field entry for a writable seat that has no saved notes', () => {
    const duty: WritableHandoverDuty = {
      dutyId: 'duty', title: 'SPK representative', cadenceId: null, cadenceLabel: '2026–2027', termStart: '2026-07-01',
      author: { id: 'me', name: 'Jonas' }, note: null, notes: [],
    };
    const wrapper = mount(HandoverNotes, { props: { notes: [], writable: [duty] } });
    expect(wrapper.get('[data-slot="handover-entry"] h3').text()).toBe('2026–2027');
    expect(wrapper.text()).toContain('Jonas');
    expect(wrapper.findAll('textarea')).toHaveLength(5);
    wrapper.unmount();
  });
});
