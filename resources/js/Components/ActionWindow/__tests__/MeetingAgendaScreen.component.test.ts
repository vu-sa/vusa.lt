import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';

import MeetingAgendaScreen from '@/Components/ActionWindow/screens/MeetingAgendaScreen.vue';
import { createActionWindowProvider, type ActionWindowContext } from '@/Composables/useActionWindow';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountScreen = () => {
  let window!: ActionWindowContext;

  const wrapper = mount(defineComponent({
    setup() {
      window = createActionWindowProvider();
      window.open({ flow: 'meeting.create', institution: { id: '1', name: 'VU SA MIF' } });
      window.goTo('meeting.agenda');
      return () => h(MeetingAgendaScreen);
    },
  }), { global: { stubs: { ...commonStubs } } });

  return { wrapper, window };
};

const choices = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findAll('[data-slot="action-choice-button"]');

describe('MeetingAgendaScreen.vue', () => {
  it('offers listing the questions, pasting them later, and skipping', () => {
    const { wrapper } = mountScreen();

    expect(choices(wrapper)).toHaveLength(3);
    expect(wrapper.text()).toContain('action_window.meeting.agenda.bulk');
  });

  it('opens the inline editor for the first choice, without the bulk flag', async () => {
    const { wrapper, window } = mountScreen();

    await choices(wrapper)[0]!.trigger('click');

    expect(wrapper.find('[data-slot="agenda-items-editor"]').exists()).toBe(true);
    expect(window.current.value.id).toBe('meeting.agenda');
    expect(window.draft.meeting.open_bulk_agenda).toBe(false);
  });

  /**
   * The bulk choice records intent only: the whole-timetable editor lives on the
   * meeting page, so the window creates the meeting and the server redirects there.
   */
  it('goes straight to the review with the bulk flag set', async () => {
    const { wrapper, window } = mountScreen();

    await choices(wrapper)[1]!.trigger('click');

    expect(window.draft.meeting.open_bulk_agenda).toBe(true);
    expect(window.draft.agendaItems).toEqual([]);
    expect(window.current.value.id).toBe('meeting.review');
  });

  it('skipping advances without the flag', async () => {
    const { wrapper, window } = mountScreen();

    await choices(wrapper)[2]!.trigger('click');

    expect(window.draft.meeting.open_bulk_agenda).toBe(false);
    expect(window.current.value.id).toBe('meeting.review');
  });

  it('keeps typed questions and clears the flag when the editor is submitted', async () => {
    const { wrapper, window } = mountScreen();

    await choices(wrapper)[0]!.trigger('click');
    await wrapper.find('[data-slot="agenda-items-editor"] input').setValue('Studijų kokybė');
    await wrapper.findAll('button').find(button => button.text().includes('action_window.common.continue'))!.trigger('click');

    expect(window.draft.agendaItems).toEqual([{ title: 'Studijų kokybė', description: '', order: 1 }]);
    expect(window.draft.meeting.open_bulk_agenda).toBe(false);
  });

  /**
   * The first row's focus ring is drawn outside its input, and the screen's scroll
   * region clips it. Whether the ring is actually visible needs a real layout engine,
   * so only the padding that leaves room for it is asserted here.
   */
  it('pads the scroll region so the first row\'s focus ring is not sheared off', () => {
    const { wrapper } = mountScreen();

    const scrollRegion = wrapper.find('[data-slot="action-window-screen"] > div.overflow-y-auto');

    expect(scrollRegion.classes()).toContain('pt-1');
    expect(scrollRegion.classes()).toContain('-mt-1');
  });
});
