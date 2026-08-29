import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

import MeetingTypeScreen from '@/Components/ActionWindow/screens/MeetingTypeScreen.vue';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

/** getMeetingTypeOptions order: in-person, remote, email, other. */
const IN_PERSON = 0;
const EMAIL = 2;

const mountScreen = (seed?: (window: ActionWindowContext) => void) => {
  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open({ flow: 'meeting.create', institution: { id: '1', name: 'VU SA MIF' } });
      seed?.(window);
      return () => h(MeetingTypeScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
};

const choices = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAll('[data-slot="action-choice-button"]');

describe('MeetingTypeScreen.vue', () => {
  it('asks when the meeting happens after picking a type', async () => {
    const { wrapper, window } = mountScreen();

    await choices(wrapper)[IN_PERSON]!.trigger('click');

    expect(window.current.value.id).toBe('meeting.when');
  });

  /**
   * The 23:59 an email meeting carries is a deadline marker, not an hour anyone agreed
   * on. Changing the type away from email on the review has to ask for a real one rather
   * than let the marker through as the meeting time.
   */
  it('asks for an hour when the type stops being email', async () => {
    const { wrapper, window } = mountScreen((w) => {
      w.updateMeeting({ type: 'email', start_time: '2026-09-15T23:59:59' });
      w.goTo('meeting.review');
      w.editFromHere('meeting.type');
    });

    await choices(wrapper)[IN_PERSON]!.trigger('click');

    expect(window.current.value.id).toBe('meeting.time');
    // The amendment still returns to the review rather than replaying the whole flow.
    expect(window.current.value.params?.returnTo).toBe('meeting.review');
  });

  it('returns straight to the review for a change that keeps the hour meaningful', async () => {
    const { wrapper, window } = mountScreen((w) => {
      w.updateMeeting({ type: 'in-person', start_time: '2026-09-15T18:00:00' });
      w.goTo('meeting.review');
      w.editFromHere('meeting.type');
    });

    await choices(wrapper)[EMAIL]!.trigger('click');

    expect(window.current.value.id).toBe('meeting.review');
    // Picking email normalises whatever hour was already chosen.
    expect(new Date(window.draft.meeting.start_time!).getHours()).toBe(23);
  });

  it('does not divert when no date has been chosen yet', async () => {
    const { wrapper, window } = mountScreen((w) => {
      w.updateMeeting({ type: 'email' });
    });

    await choices(wrapper)[IN_PERSON]!.trigger('click');

    expect(window.current.value.id).toBe('meeting.when');
  });
});
