import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import MeetingAgendaList from '../MeetingAgendaList.vue';

import { commonStubs } from '@/tests/stubs';

const baseStubs = {
  ...commonStubs,
  // Visual children we don't need to exercise here
  SpotlightPopover: { template: '<div><slot /></div>' },
  AdminVotingHelpButton: { template: '<div />' },
  VoteStatusIndicator: { template: '<span class="vote-indicator" />' },
  Switch: {
    props: ['modelValue'],
    emits: ['update:modelValue'],
    template: '<button class="switch" @click="$emit(\'update:modelValue\', !modelValue)" />',
  },
};

const makeItem = (overrides: Record<string, any> = {}) => ({
  id: 'item-1',
  meeting_id: 'm1',
  title: 'Test agenda item',
  order: 1,
  brought_by_students: false,
  type: 'voting',
  votes: [],
  ...overrides,
});

describe('MeetingAgendaList', () => {
  it('renders read-only rows linking to the per-item edit page', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem()] as any, meetingId: 'm1' },
      global: { stubs: baseStubs },
    });

    const link = wrapper.find('a');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toContain('agendaItems.edit');
    expect(wrapper.text()).toContain('Test agenda item');
  });

  it('does not show drag handles or remove buttons in read-only mode', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem()] as any, meetingId: 'm1', editing: false },
      global: { stubs: baseStubs },
    });

    expect(wrapper.find('.drag-handle').exists()).toBe(false);
    expect(wrapper.find('[aria-label="Šalinti"]').exists()).toBe(false);
  });

  it('reveals drag handles and a remove button in edit mode', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem()] as any, meetingId: 'm1', editing: true },
      global: { stubs: baseStubs },
    });

    expect(wrapper.find('.drag-handle').exists()).toBe(true);
    expect(wrapper.find('[aria-label="Šalinti"]').exists()).toBe(true);
  });

  it('emits delete with the item when the remove button is clicked', async () => {
    const item = makeItem();
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [item] as any, meetingId: 'm1', editing: true },
      global: { stubs: baseStubs },
    });

    await wrapper.find('[aria-label="Šalinti"]').trigger('click');

    expect(wrapper.emitted('delete')?.[0]?.[0]).toMatchObject({ id: 'item-1' });
  });

  /**
   * A pasted timetable is the usual way an empty agenda gets filled, and the bulk
   * editor used to be reachable only from the add menu, which needs an item first.
   */
  it('offers both the single and the bulk editor from the empty state', async () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [] as any, meetingId: 'm1', editing: true },
      global: { stubs: baseStubs },
    });

    const buttons = wrapper.findAll('button');
    const bulk = buttons.find(button => button.text().includes('Pridėti kelis punktus'));

    expect(buttons.some(button => button.text().includes('Pridėti pirmą klausimą'))).toBe(true);
    expect(bulk).toBeDefined();

    await bulk!.trigger('click');
    expect(wrapper.emitted('add-bulk')).toHaveLength(1);
  });

  it('hides both add affordances on an empty agenda in read-only mode', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [] as any, meetingId: 'm1', editing: false },
      global: { stubs: baseStubs },
    });

    expect(wrapper.text()).not.toContain('Pridėti kelis punktus');
    expect(wrapper.text()).not.toContain('Pridėti pirmą klausimą');
  });

  it('shows the vote count label only when there is more than one vote', () => {
    const single = mount(MeetingAgendaList, {
      props: {
        agendaItems: [makeItem({ votes: [{ id: 'v1', is_main: true, decision: 'positive' }] })] as any,
        meetingId: 'm1',
      },
      global: { stubs: baseStubs },
    });
    expect(single.text()).not.toContain('balsavimai');

    const multiple = mount(MeetingAgendaList, {
      props: {
        agendaItems: [makeItem({
          votes: [
            { id: 'v1', is_main: true, decision: 'positive' },
            { id: 'v2', is_main: false, decision: 'negative' },
          ],
        })] as any,
        meetingId: 'm1',
      },
      global: { stubs: baseStubs },
    });
    expect(multiple.text()).toContain('balsavimai');
  });

  it('counts a decision-only vote as discussed for an internal body', () => {
    const items = [
      makeItem({ votes: [{ id: 'v1', is_main: true, decision: 'positive' }] }),
      makeItem({ id: 'item-2', order: 2, votes: [] }),
    ];

    const internal = mount(MeetingAgendaList, {
      props: { agendaItems: items as any, meetingId: 'm1', requiresStudentPerspective: false },
      global: { stubs: baseStubs },
    });
    expect(internal.text()).toContain('1 iš 2');

    // External bodies still wait for the student perspective before calling a vote discussed
    const external = mount(MeetingAgendaList, {
      props: { agendaItems: items as any, meetingId: 'm1' },
      global: { stubs: baseStubs },
    });
    expect(external.text()).toContain('0 iš 2');
  });
});
