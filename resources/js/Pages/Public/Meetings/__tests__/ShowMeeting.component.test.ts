import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import ShowMeeting from '@/Pages/Public/Meetings/ShowMeeting.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

const stubs = {
  PublicVotingExplainerModal: { template: '<div />' },
  FeedbackPopover: { template: '<div />' },
  UserAvatar: { template: '<div />' },
};

function makeMeeting(overrides: Record<string, unknown> = {}) {
  return {
    id: 'meet-1',
    start_time: '2030-01-01T18:00:00+00:00',
    description: '',
    agenda_items: [
      { id: 'a1', title: 'Dėl veiklos plano', order: 1, type: 'informational', start_time: '18:30:00', end_time: '19:00:00' },
    ],
    ...overrides,
  };
}

describe('Public/Meetings/ShowMeeting.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  function mountPage(props: Record<string, unknown> = {}) {
    return mount(ShowMeeting, {
      props: {
        meeting: makeMeeting(),
        institution: { id: 'inst-1', name: 'VU SA Parlamentas' },
        representatives: [],
        ...props,
      },
      global: { stubs },
    });
  }

  it('shows student representatives immediately below the title instead of description', () => {
    const wrapper = mountPage({
      meeting: makeMeeting({ description: 'Trumpas aprašymas' }),
      representatives: [
        { id: 'u1', name: 'Jonas Jonaitis', profile_photo_path: '/photos/jonas.jpg' },
      ],
    });

    const text = wrapper.text();
    expect(text).toContain('Jonas Jonaitis');
    expect(text).not.toContain('Trumpas aprašymas');

    const repIndex = text.indexOf('Jonas Jonaitis');
    const timeIndex = text.indexOf('18:30');

    expect(repIndex).toBeGreaterThan(-1);
    expect(timeIndex).toBeGreaterThan(repIndex);
    expect(text.split('18:30')).toHaveLength(2);
  });

  it('never assembles a timetable card of its own', () => {
    const wrapper = mountPage({
      meeting: makeMeeting({ agenda_items: [{ id: 'a1', title: 'Klausimas', order: 1, type: 'informational' }] }),
    });

    expect(wrapper.text()).not.toContain('Tvarkaraštis');
  });

  it('does not show the outcome summary or question count', () => {
    const wrapper = mountPage({
      meeting: makeMeeting({
        agenda_items: [
          { id: 'a1', title: 'Pirmas', order: 1, main_vote: { decision: 'positive' } },
          { id: 'a2', title: 'Antras', order: 2, main_vote: { decision: 'positive' } },
          { id: 'a3', title: 'Trečias', order: 3, main_vote: { decision: 'negative' } },
          { id: 'a4', title: 'Ketvirtas', order: 4 },
        ],
      }),
    });

    expect(wrapper.find('[data-testid="outcome-summary"]').exists()).toBe(false);
    expect(wrapper.text()).not.toContain('Sprendimų santrauka');
    expect(wrapper.text()).not.toContain('klausimas');
    expect(wrapper.text()).not.toContain('klausimai');
  });

  it('does not underline the institution link in the eyebrow', () => {
    const wrapper = mountPage({
      institution: { id: 'inst-1', name: 'VU SA Parlamentas' },
    });

    const institutionLink = wrapper.findAll('a').find(link => link.text() === 'VU SA Parlamentas');
    expect(institutionLink?.classes()).toContain('no-underline');
  });
});
