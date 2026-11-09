import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import HandoverNotes from '../HandoverNotes.vue';

describe('HandoverNotes', () => {
  it('renders the sanitized rich body with duty author and date', () => {
    const wrapper = mount(HandoverNotes, { props: { notes: [{
      id: 'note', dutyId: 'duty', duty: 'SPK representative', author: { id: 'author', name: 'Rūta' },
      body: '<p><strong>Unfinished matters</strong></p><ul><li>Discuss the proposal</li></ul>',
      createdAt: '2026-11-01T12:00:00+02:00', updatedAt: '2026-11-02T12:00:00+02:00',
    }] } });
    expect(wrapper.get('h3').text()).toBe('SPK representative');
    expect(wrapper.text()).toContain('Rūta');
    expect(wrapper.get('time').attributes('datetime')).toBe('2026-11-02T12:00:00+02:00');
    expect(wrapper.get('[data-slot="handover-body"] strong').text()).toBe('Unfinished matters');
    expect(wrapper.get('[data-slot="handover-body"] li').text()).toBe('Discuss the proposal');
    wrapper.unmount();
  });
});
