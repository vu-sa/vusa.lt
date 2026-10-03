import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { router } from '@inertiajs/vue3';

import AddAgendaItemsSheet from '@/Components/Meetings/AddAgendaItemsSheet.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const SheetFormStub = {
  name: 'SheetForm',
  props: ['open', 'disabled', 'saveLabel'],
  emits: ['submit', 'cancel', 'update:open'],
  template: '<form @submit.prevent="$emit(\'submit\')"><slot /><button class="save" type="submit">{{ saveLabel }}</button></form>',
};

const mountSheet = (props: Record<string, unknown> = {}) => mount(AddAgendaItemsSheet, {
  props: { open: true, meetingId: 'm1', ...props },
  global: { stubs: { ...commonStubs, SheetForm: SheetFormStub } },
});

describe('AddAgendaItemsSheet', () => {
  beforeEach(() => vi.clearAllMocks());

  it('adds a pasted timetable with its times', async () => {
    const wrapper = mountSheet({ initialMode: 'paste' });

    await wrapper.find('#agenda-paste').setValue('1. 10.00–10.30 Studijų tvarka\n2. Kiti klausimai');
    await wrapper.find('form').trigger('submit');

    expect(router.post).toHaveBeenCalledWith(expect.stringContaining('agendaItems.store'), {
      meeting_id: 'm1',
      agendaItemTitles: ['Studijų tvarka', 'Kiti klausimai'],
      startTimes: ['10:00', null],
      endTimes: ['10:30', null],
    }, expect.anything());
  });

  it('fetches earlier agendas only when that mode is opened', async () => {
    const wrapper = mountSheet();
    expect(router.reload).not.toHaveBeenCalled();

    await wrapper.find('[data-testid="agenda-add-mode-previous"]').trigger('click');

    expect(router.reload).toHaveBeenCalledWith(expect.objectContaining({ only: ['recentAgendas'] }));
  });

  /** A template is a starting point: it lands in the editable list, not on the agenda. */
  it('turns a picked earlier agenda into editable lines', async () => {
    const wrapper = mountSheet({
      initialMode: 'previous',
      recentAgendas: [{ id: 'old', start_time: '2026-05-01T10:00:00Z', institution_name: 'VU Senatas', agenda_items: ['Biudžetas', 'Kita'] }],
    });

    await wrapper.findAll('button').find(button => button.text().includes('VU Senatas'))!.trigger('click');

    const inputs = wrapper.findAll('[data-slot="agenda-items-editor"] input');
    expect(inputs.map(input => (input.element as HTMLInputElement).value)).toEqual(['Biudžetas', 'Kita']);
    expect(router.post).not.toHaveBeenCalled();
  });
});
