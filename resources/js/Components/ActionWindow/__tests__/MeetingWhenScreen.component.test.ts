import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MeetingWhenScreen from '@/Components/ActionWindow/screens/MeetingWhenScreen.vue';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const state = { pattern: null as { weekday: number; time: string } | null, loading: false };

vi.mock('@/Composables/useActionWindowData', () => ({
  useActionWindowData: () => ({
    institutions: { value: [{ id: '1', name: 'MIF SPK', is_internal: true, meeting_pattern: state.pattern }] },
    meetings: { value: [] },
    isLoading: { value: state.loading },
    error: { value: null },
    load: vi.fn(),
  }),
  invalidateActionWindowData: vi.fn(),
}));

/**
 * Today and yesterday are always offered — most meetings are filed the day of or the day
 * after. Anything further ahead is only ever suggested from this body's own history: a
 * generic "next Tuesday at 18:00" is wrong for almost every institution, and a wrong
 * default is worse than none.
 */
const mountScreen = (type?: string) => {
  vi.mocked(usePage).mockReturnValue(createMockPage());

  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open({ flow: 'meeting.create', institution: { id: '1', name: 'MIF SPK' } });
      if (type) {
        window.updateMeeting({ type: type as never });
      }
      window.goTo('meeting.when');
      return () => h(MeetingWhenScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
};

const choices = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAll('[data-slot="action-choice-button"]').map(button => button.text());

describe('MeetingWhenScreen.vue', () => {
  beforeEach(() => {
    state.pattern = null;
    state.loading = false;
    vi.mocked(usePage).mockReset();
  });

  it('leads with today and yesterday, then the next two occurrences of the weekday this body meets on', async () => {
    state.pattern = { weekday: 2, time: '18:30' };
    const { wrapper } = mountScreen();
    await flushPromises();

    const suggestions = choices(wrapper);
    expect(suggestions).toHaveLength(5);

    expect(suggestions[0]).toContain('action_window.meeting.when.today');
    expect(suggestions[1]).toContain('action_window.meeting.when.yesterday');
    // The time is part of the answer, so it must be visible in the choice itself.
    expect(suggestions[2]).toContain('18:30');
    expect(suggestions[3]).toContain('18:30');
    expect(suggestions[4]).toContain('action_window.meeting.when.custom');
  });

  it('offers today, yesterday and a custom date when the body has never met', async () => {
    const { wrapper, window } = mountScreen();
    await flushPromises();

    const suggestions = choices(wrapper);
    expect(suggestions).toHaveLength(3);
    expect(suggestions[2]).toContain('action_window.meeting.when.custom');
    expect(window.current.value.id).toBe('meeting.when');
  });

  it('picks a future date on that weekday, at that hour, as a local wall clock', async () => {
    state.pattern = { weekday: 4, time: '17:00' }; // Thursday
    const { wrapper, window } = mountScreen();
    await flushPromises();

    await wrapper.findAll('[data-slot="action-choice-button"]')[2]!.trigger('click');

    const chosen = new Date(window.draft.meeting.start_time!);
    const isoWeekday = chosen.getDay() === 0 ? 7 : chosen.getDay();

    expect(isoWeekday).toBe(4);
    expect(chosen.getHours()).toBe(17);
    expect(chosen.getMinutes()).toBe(0);
    expect(chosen.getTime()).toBeGreaterThan(Date.now());
    // A UTC instant would move the meeting hours away from the hour just chosen.
    expect(window.draft.meeting.start_time).not.toContain('Z');
  });

  it('the second suggestion is a week after the first', async () => {
    state.pattern = { weekday: 2, time: '18:30' };
    const { wrapper, window } = mountScreen();
    await flushPromises();

    await wrapper.findAll('[data-slot="action-choice-button"]')[3]!.trigger('click');
    const later = new Date(window.draft.meeting.start_time!);

    window.backTo('meeting.when');
    await flushPromises();
    await wrapper.findAll('[data-slot="action-choice-button"]')[2]!.trigger('click');
    const sooner = new Date(window.draft.meeting.start_time!);

    expect(later.getTime() - sooner.getTime()).toBe(7 * 24 * 60 * 60 * 1000);
  });

  /**
   * An email meeting is a deadline, not an appointment. The weekday still comes from
   * this body's history, but the hour behind it is an in-person one — showing it would
   * promise a time the meeting does not have.
   */
  describe('an email meeting', () => {
    it('suggests the day without an hour', async () => {
      state.pattern = { weekday: 2, time: '18:30' };
      const { wrapper } = mountScreen('email');
      await flushPromises();

      const suggestions = choices(wrapper);
      expect(suggestions[2]).not.toContain('18:30');
      expect(suggestions[3]).not.toContain('18:30');
    });

    it('stores the picked day as a 23:59 deadline rather than the usual hour', async () => {
      state.pattern = { weekday: 2, time: '18:30' };
      const { wrapper, window } = mountScreen('email');
      await flushPromises();

      await wrapper.findAll('[data-slot="action-choice-button"]')[2]!.trigger('click');

      const chosen = new Date(window.draft.meeting.start_time!);
      expect(chosen.getHours()).toBe(23);
      expect(chosen.getMinutes()).toBe(59);
    });

    it('normalises an hour that was already picked before the type changed', async () => {
      state.pattern = { weekday: 2, time: '18:30' };
      const { wrapper, window } = mountScreen();
      await flushPromises();

      await wrapper.findAll('[data-slot="action-choice-button"]')[2]!.trigger('click');
      expect(new Date(window.draft.meeting.start_time!).getHours()).toBe(18);

      window.updateMeeting({ type: 'email' });

      const chosen = new Date(window.draft.meeting.start_time!);
      expect(chosen.getHours()).toBe(23);
      expect(chosen.getMinutes()).toBe(59);
    });
  });

  describe('the day presets', () => {
    const daysBetweenNowAnd = (iso: string) => {
      const picked = new Date(iso);
      const today = new Date();
      picked.setHours(0, 0, 0, 0);
      today.setHours(0, 0, 0, 0);

      return Math.round((picked.getTime() - today.getTime()) / 86_400_000);
    };

    it('picks today at the body\'s usual hour and moves on to confirm the time', async () => {
      state.pattern = { weekday: 2, time: '17:30' };
      const { wrapper, window } = mountScreen();
      await flushPromises();

      await wrapper.findAll('[data-slot="action-choice-button"]')[0]!.trigger('click');

      const chosen = new Date(window.draft.meeting.start_time!);
      expect(daysBetweenNowAnd(window.draft.meeting.start_time!)).toBe(0);
      expect(chosen.getHours()).toBe(17);
      expect(chosen.getMinutes()).toBe(30);
      expect(window.current.value.id).toBe('meeting.time');
    });

    it('picks yesterday, falling back to 18:00 when the body has no usual hour', async () => {
      const { wrapper, window } = mountScreen();
      await flushPromises();

      await wrapper.findAll('[data-slot="action-choice-button"]')[1]!.trigger('click');

      const chosen = new Date(window.draft.meeting.start_time!);
      expect(daysBetweenNowAnd(window.draft.meeting.start_time!)).toBe(-1);
      expect(chosen.getHours()).toBe(18);
      expect(window.current.value.id).toBe('meeting.time');
    });

    it('for an email meeting stores a 23:59 deadline and skips the clock', async () => {
      const { wrapper, window } = mountScreen('email');
      await flushPromises();

      await wrapper.findAll('[data-slot="action-choice-button"]')[1]!.trigger('click');

      const chosen = new Date(window.draft.meeting.start_time!);
      expect(chosen.getHours()).toBe(23);
      expect(chosen.getMinutes()).toBe(59);
      expect(window.current.value.id).toBe('meeting.agenda');
    });

    it('keeps an hour already chosen when only the day changes from the review', async () => {
      const { wrapper, window } = mountScreen();
      window.updateMeeting({ start_time: '2026-03-02T09:15:00' });
      window.goTo('meeting.review');
      window.editFromHere('meeting.when');
      await flushPromises();

      await wrapper.findAll('[data-slot="action-choice-button"]')[0]!.trigger('click');

      const chosen = new Date(window.draft.meeting.start_time!);
      expect(chosen.getHours()).toBe(9);
      expect(chosen.getMinutes()).toBe(15);
      // An amendment goes straight back rather than walking the rest of the flow again.
      expect(window.current.value.id).toBe('meeting.review');
    });
  });

  it('opens the date field for anything else, carrying the return frame', async () => {
    const { wrapper, window } = mountScreen();
    window.goTo('meeting.review');
    window.editFromHere('meeting.when');
    await flushPromises();

    const buttons = wrapper.findAll('[data-slot="action-choice-button"]');
    await buttons[buttons.length - 1]!.trigger('click');

    expect(window.current.value.id).toBe('meeting.date');
    expect(window.current.value.params?.returnTo).toBe('meeting.review');
  });

  it('waits for the fetch before offering anything', async () => {
    state.loading = true;
    const { wrapper } = mountScreen();
    await flushPromises();

    expect(choices(wrapper)).toHaveLength(0);
  });
});
