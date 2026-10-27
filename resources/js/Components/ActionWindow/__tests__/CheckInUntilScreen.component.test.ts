import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import CheckInUntilScreen from '@/Components/ActionWindow/screens/CheckInUntilScreen.vue';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const state = {
  from: null as string | null,
  reason: null as string | null,
  nextMeeting: null as string | null,
};

vi.mock('@/Composables/useActionWindowData', async () => {
  const { ref } = await import('vue');

  return {
    useActionWindowData: () => ({
      institutions: ref([{
        id: '1',
        name: 'MIF SPK',
        check_in_from: state.from,
        check_in_from_reason: state.reason,
        activity_status: { next_meeting_at: state.nextMeeting },
      }]),
      isLoading: ref(false),
      load: vi.fn(),
    }),
    invalidateActionWindowData: vi.fn(),
  };
});

const mountScreen = () => {
  vi.mocked(usePage).mockReturnValue(createMockPage());

  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open({ flow: 'check-in', institution: { id: '1', name: 'MIF SPK' } });
      return () => h(CheckInUntilScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
};

const choices = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('[data-slot="action-choice-button"]');

describe('CheckInUntilScreen.vue', () => {
  beforeEach(() => {
    vi.useFakeTimers({ toFake: ['Date'] });
    vi.setSystemTime(new Date(2026, 9, 16, 12));
    state.from = null;
    state.reason = null;
    state.nextMeeting = null;
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('covers the time since the term started, up to today', async () => {
    state.from = '2026-09-01';
    state.reason = 'tenure_start';
    const { wrapper, window } = mountScreen();
    await flushPromises();

    expect(choices(wrapper).map(choice => choice.text())).toEqual([
      expect.stringContaining('action_window.check_in.until.until_today'),
      expect.stringContaining('action_window.check_in.until.two_weeks'),
      expect.stringContaining('action_window.check_in.until.month_end'),
      expect.stringContaining('action_window.check_in.until.custom'),
    ]);

    await choices(wrapper)[0]!.trigger('click');

    expect(window.draft.checkIn).toMatchObject({ startDate: '2026-09-01', endDate: '2026-10-16' });
    expect(window.current.value.id).toBe('checkin.review');
  });

  it('only looks ahead, from today, when there is nothing to start from', async () => {
    const { wrapper, window } = mountScreen();
    await flushPromises();

    expect(wrapper.text()).not.toContain('action_window.check_in.until.until_today');

    await choices(wrapper)[0]!.trigger('click');

    expect(window.draft.checkIn).toMatchObject({ startDate: '2026-10-16', endDate: '2026-10-30' });
  });

  it('ends the period the day before an upcoming meeting', async () => {
    state.from = '2026-09-01';
    state.reason = 'last_meeting';
    state.nextMeeting = '2026-10-20T15:00:00.000Z';
    const { wrapper, window } = mountScreen();
    await flushPromises();

    expect(choices(wrapper)).toHaveLength(3);
    expect(wrapper.text()).toContain('action_window.check_in.until.next_meeting_cap');

    // Each choice names its whole range, not just where it ends.
    expect(choices(wrapper)[1]!.text()).toMatch(/\S+ 1 – \S+ 19/);

    await choices(wrapper)[1]!.trigger('click');

    expect(window.draft.checkIn).toMatchObject({ startDate: '2026-09-01', endDate: '2026-10-19' });
  });

  it('names the years only when the period crosses into the next one', async () => {
    vi.setSystemTime(new Date(2026, 11, 22, 12));
    state.from = '2026-12-01';
    state.reason = 'tenure_start';
    const { wrapper } = mountScreen();
    await flushPromises();

    expect(choices(wrapper)[0]!.text()).not.toContain('2026');
    expect(choices(wrapper)[1]!.text()).toMatch(/2026.* – .*2027/);
  });
});
