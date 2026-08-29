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

  // The page used to repeat itself: a standalone timetable card above the description and
  // the agenda list with the same times below it. Only the agenda list remains — the
  // timetable is now a content block usable anywhere, not something this page assembles.
  it('shows agenda times once, in the agenda list below the description', () => {
    const wrapper = mountPage({ meeting: makeMeeting({ description: 'Trumpas aprašymas' }) });

    const text = wrapper.text();
    const descriptionIndex = text.indexOf('Trumpas aprašymas');
    const timeIndex = text.indexOf('18:30');

    expect(descriptionIndex).toBeGreaterThan(-1);
    expect(timeIndex).toBeGreaterThan(descriptionIndex);
    expect(text.split('18:30')).toHaveLength(2);
  });

  it('never assembles a timetable card of its own', () => {
    const wrapper = mountPage({
      meeting: makeMeeting({ agenda_items: [{ id: 'a1', title: 'Klausimas', order: 1, type: 'informational' }] }),
    });

    expect(wrapper.text()).not.toContain('Tvarkaraštis');
  });
});
