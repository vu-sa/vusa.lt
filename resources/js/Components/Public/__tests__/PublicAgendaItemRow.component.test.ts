import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import PublicAgendaItemRow from '../PublicAgendaItemRow.vue';

describe('PublicAgendaItemRow', () => {
  it('renders only the number and public title without a privacy label for a private item', () => {
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
    expect(wrapper.text()).not.toContain('meetings.privacy.internal_only');
    expect(wrapper.text()).not.toContain('Internal content', '14:00', 'Įtraukta studentų');
    expect(wrapper.find('details').exists()).toBe(false);
    expect(wrapper.find('dl').exists()).toBe(false);
  });

  it('shows the student position, but not for a private item or a VU SA body', () => {
    const render = (item: object, requiresStudentPerspective = true) => mount(PublicAgendaItemRow, {
      props: { requiresStudentPerspective, item: { id: 'a', order: 1, title: 'Klausimas', student_position: 'Mūsų pozicija', ...item } as unknown as App.Entities.AgendaItem },
    });

    expect(render({}).get('[data-slot="public-student-position"]').text()).toContain('Mūsų pozicija');
    expect(render({ is_private: true }).find('[data-slot="public-student-position"]').exists()).toBe(false);
    expect(render({}, false).find('[data-slot="public-student-position"]').exists()).toBe(false);
  });

  it('lists every vote when none is main, with the combined status', () => {
    const wrapper = mount(PublicAgendaItemRow, {
      props: {
        item: {
          id: 'item', order: 1, title: 'Studijų programos', is_private: false, type: 'voting',
          votes: [
            { id: 'a', is_main: false, title: 'Pirmas balsavimas', decision: 'positive', student_vote: 'positive', student_benefit: 'positive' },
            { id: 'b', is_main: false, title: 'Antras balsavimas', decision: 'negative', student_vote: 'positive', student_benefit: 'negative' },
          ],
        } as unknown as App.Entities.AgendaItem,
      },
    });

    expect(wrapper.text()).toContain('Skirtingi rezultatai');
    expect(wrapper.findAll('[data-testid="public-agenda-vote"]')).toHaveLength(2);
    expect(wrapper.text()).toContain('Pirmas balsavimas');
    expect(wrapper.text()).toContain('Antras balsavimas');
  });
});
