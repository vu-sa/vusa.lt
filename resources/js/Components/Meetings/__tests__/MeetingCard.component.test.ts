import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import MeetingCard from '../MeetingCard.vue';
import { titleWithoutGeneratedDate } from '../meetingTitle';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('titleWithoutGeneratedDate', () => {
  it.each([
    ['2026 balandžio 23 d. 15.00 val. posėdis', 'Posėdis'],
    ['2026 gegužės 04 d. sprendimas el. paštu', 'Sprendimas el. paštu'],
    ['23 April 2026 15.00 sitting', 'Sitting'],
    ['Metinė ataskaitinė konferencija', 'Metinė ataskaitinė konferencija'],
  ])('reduces "%s" to "%s"', (title, expected) => {
    expect(titleWithoutGeneratedDate(title)).toBe(expected);
  });

  it('has nothing to show for an empty title', () => {
    expect(titleWithoutGeneratedDate('  ')).toBeNull();
  });
});

describe('MeetingCard', () => {
  const meeting = {
    id: 'm1',
    title: '2026 balandžio 23 d. 15.00 val. posėdis',
    start_time: '2026-04-23 15:00:00',
    agenda_items_count: 4,
    vote_matches: 2,
    vote_mismatches: 1,
    has_protocol: true,
    has_report: false,
  };

  it('links to the meeting and does not repeat the date the plate already shows', () => {
    const wrapper = mount(MeetingCard, { props: { meeting, href: '/mano/meetings/m1' } });

    expect(wrapper.get('a').attributes('href')).toBe('/mano/meetings/m1');
    expect(wrapper.text()).toContain('Posėdis');
    expect(wrapper.text()).not.toContain('balandžio 23 d.');
    expect(wrapper.get('time').attributes('datetime')).toBe(meeting.start_time);
  });

  it('sums up the agenda, the votes and the documents', () => {
    const wrapper = mount(MeetingCard, { props: { meeting, href: '/mano/meetings/m1' } });

    expect(wrapper.text()).toContain('meetings.card.agenda_items');
    expect(wrapper.get('[data-slot="meeting-card-votes"]').text()).toContain('2');
    expect(wrapper.text()).toContain('Protokolas');
    expect(wrapper.text()).not.toContain('Ataskaita');
  });

  it('marks a meeting still to come', () => {
    const upcoming = mount(MeetingCard, { props: { meeting: { ...meeting, start_time: '2999-01-01 10:00:00' }, href: '/m' } });
    const past = mount(MeetingCard, { props: { meeting, href: '/m' } });

    expect(upcoming.find('[data-slot="meeting-card-upcoming"]').exists()).toBe(true);
    expect(past.find('[data-slot="meeting-card-upcoming"]').exists()).toBe(false);
  });
});
