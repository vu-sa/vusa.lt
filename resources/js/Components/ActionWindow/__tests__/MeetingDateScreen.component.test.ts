import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import MeetingDateScreen from '@/Components/ActionWindow/screens/MeetingDateScreen.vue';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountScreen = (setup?: (window: ActionWindowContext) => void) => {
  vi.mocked(usePage).mockReturnValue(createMockPage());

  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open({ flow: 'meeting.create', institution: { id: '1', name: 'MIF SPK' } });
      setup?.(window);
      window.goTo('meeting.date');
      return () => h(MeetingDateScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
};

const typeDate = async (wrapper: ReturnType<typeof mount>, value: string) => {
  const input = wrapper.find('input');
  await input.setValue(value);
  await flushPromises();
};

const confirm = (wrapper: ReturnType<typeof mount>) => wrapper.find('[data-slot="action-window-primary"]').trigger('click');

describe('MeetingDateScreen.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReset();
  });

  it('starts on the day already chosen, so changing only the hour is not a re-entry', () => {
    const { wrapper } = mountScreen(window => window.updateMeeting({ start_time: '2026-03-02T09:15:00' }));

    expect((wrapper.find('input').element as HTMLInputElement).value).toBe('2026-03-02');
  });

  it('carries the typed day into the clock step, keeping the hour already chosen', async () => {
    const { wrapper, window } = mountScreen(w => w.updateMeeting({ start_time: '2026-03-02T09:15:00' }));

    await typeDate(wrapper, '2026-04-07');
    await confirm(wrapper);

    const chosen = new Date(window.draft.meeting.start_time!);
    expect([chosen.getFullYear(), chosen.getMonth() + 1, chosen.getDate()]).toEqual([2026, 4, 7]);
    expect([chosen.getHours(), chosen.getMinutes()]).toEqual([9, 15]);
    expect(window.current.value.id).toBe('meeting.time');
  });

  it('for an email meeting stores a 23:59 deadline and goes straight to the agenda', async () => {
    const { wrapper, window } = mountScreen(w => w.updateMeeting({ type: 'email' }));

    await typeDate(wrapper, '2026-04-07');
    await confirm(wrapper);

    const chosen = new Date(window.draft.meeting.start_time!);
    expect([chosen.getDate(), chosen.getHours(), chosen.getMinutes()]).toEqual([7, 23, 59]);
    expect(window.current.value.id).toBe('meeting.agenda');
  });

  it('cannot continue without a valid day', async () => {
    const { wrapper } = mountScreen();

    await typeDate(wrapper, 'not a date');

    expect(wrapper.find('[data-slot="action-window-primary"]').attributes('disabled')).toBeDefined();
  });
});
