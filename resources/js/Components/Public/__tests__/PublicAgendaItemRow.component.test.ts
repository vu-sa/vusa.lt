import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import PublicAgendaItemRow from '../PublicAgendaItemRow.vue';

describe('PublicAgendaItemRow', () => {
  it('renders only the number, public title and privacy label for a private item', () => {
    const wrapper = mount(PublicAgendaItemRow, {
      props: {
        item: {
          id: 'private', order: 3, title: 'Working item', is_private: true,
          description: 'Internal content', type: 'voting', brought_by_students: true,
          start_time: '14:00:00', end_time: '14:30:00',
          votes: [{ is_main: true, decision: 'positive', student_vote: 'negative', student_benefit: 'neutral' }],
        } as unknown as App.Entities.AgendaItem,
      },
    });

    expect(wrapper.text()).toContain('3');
    expect(wrapper.text()).toContain('Working item');
    expect(wrapper.text()).toContain('meetings.privacy.internal_only');
    expect(wrapper.text()).not.toContain('Internal content', '14:00', 'Įtraukta studentų');
    expect(wrapper.find('details').exists()).toBe(false);
    expect(wrapper.find('dl').exists()).toBe(false);
  });
});
