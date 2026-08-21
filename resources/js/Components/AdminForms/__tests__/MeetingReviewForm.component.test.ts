import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import MeetingReviewForm from '@/Components/AdminForms/MeetingReviewForm.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

function makeMeetingState(overrides: Record<string, unknown> = {}) {
  return {
    institution: { id: 'inst-1', name: 'VU SA Parlamentas' },
    meeting: {
      start_time: '2030-01-01T18:00:00+00:00',
      type: null,
      description: '',
      announce_in_calendar: false,
    },
    agendaItems: [],
    ...overrides,
  };
}

describe('AdminForms/MeetingReviewForm.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  function mountForm(props: Record<string, unknown> = {}) {
    return mount(MeetingReviewForm, {
      props: { meetingState: makeMeetingState(), ...props },
    });
  }

  it('tells the editor up front that the meeting can be added to the public calendar', () => {
    const wrapper = mountForm();

    // The test-suite's $t() mock echoes the key verbatim (see tests/setup.ts) — dot-notation
    // PHP-lang keys, unlike lang/lt.json's self-referential flat keys, don't resolve to real text.
    expect(wrapper.text()).toContain('meetings.announce.review_intro');
  });

  it('renders the announce-in-calendar checkbox unchecked by default', () => {
    const wrapper = mountForm();

    const checkbox = wrapper.find('[role="checkbox"]');
    expect(checkbox.exists()).toBe(true);
    expect(checkbox.attributes('data-state')).toBe('unchecked');
  });

  it('reflects an already-true announce_in_calendar as checked', () => {
    const wrapper = mountForm({
      meetingState: makeMeetingState({ meeting: { ...makeMeetingState().meeting, announce_in_calendar: true } }),
    });

    expect(wrapper.find('[role="checkbox"]').attributes('data-state')).toBe('checked');
  });

  it('emits update:announceInCalendar when the checkbox is toggled', async () => {
    const wrapper = mountForm();

    await wrapper.find('[role="checkbox"]').trigger('click');

    expect(wrapper.emitted('update:announceInCalendar')).toEqual([[true]]);
  });
});
