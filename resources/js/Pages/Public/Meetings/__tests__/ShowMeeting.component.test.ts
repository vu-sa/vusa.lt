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

  it('shows the timetable above the description when agenda items carry a time', () => {
    const wrapper = mountPage({ meeting: makeMeeting({ description: 'Trumpas aprašymas' }) });

    const text = wrapper.text();
    const timetableIndex = text.indexOf('18:30');
    const descriptionIndex = text.indexOf('Trumpas aprašymas');

    expect(timetableIndex).toBeGreaterThan(-1);
    expect(descriptionIndex).toBeGreaterThan(-1);
    expect(timetableIndex).toBeLessThan(descriptionIndex);
  });

  it('renders no timetable when no agenda item has a start time', () => {
    const wrapper = mountPage({
      meeting: makeMeeting({ agenda_items: [{ id: 'a1', title: 'Klausimas', order: 1, type: 'informational' }] }),
    });

    expect(wrapper.text()).not.toContain('Tvarkaraštis');
  });
});
