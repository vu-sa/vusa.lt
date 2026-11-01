import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import MeetingCard from '../MeetingCard.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const vote = { is_main: true, decision: 'positive', student_vote: 'positive', student_benefit: 'positive' };

const render = (type: string | null, meeting: object = {}) => mount(MeetingCard, {
  props: { meeting: { id: 'm1', start_time: '2026-10-01 14:00:00', agenda_items: [{ id: 'a', order: 1, title: 'Klausimas', type, votes: [vote] }], ...meeting } } as never,
  global: { stubs: { SmartLink: { template: '<a><slot /></a>' }, VoteStatusIndicator: true, AgendaOutcomeIndicators: { name: 'AgendaOutcomeIndicators', props: ['agendaItems'], template: '<i />' } } },
});

describe('MeetingCard', () => {
  it('shows the votes of a voting item', () => {
    const wrapper = render('voting');

    expect(wrapper.text()).toContain('Sprendimas');
    expect(wrapper.findComponent({ name: 'AgendaOutcomeIndicators' }).props('agendaItems')).toHaveLength(1);
  });

  it.each([['deferred', 'Atidėtas'], ['withdrawn', 'Išbrauktas']])('shows a %s item its status instead of leftover votes', (type, label) => {
    const wrapper = render(type);

    expect(wrapper.text()).not.toContain('Sprendimas');
    expect(wrapper.get('[data-slot="meeting-card-item-status"]').text()).toBe(label);
    expect(wrapper.findComponent({ name: 'AgendaOutcomeIndicators' }).props('agendaItems')).toHaveLength(0);
  });

  it('shows a VU SA body only the decision', () => {
    const wrapper = render('voting', { requires_student_perspective: false });

    expect(wrapper.text()).toContain('Sprendimas');
    expect(wrapper.text()).not.toContain('Studentų balsas');
  });
});
