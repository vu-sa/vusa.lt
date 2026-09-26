import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import MeetingAgendaList from '../MeetingAgendaList.vue';

import { commonStubs } from '@/tests/stubs';

const baseStubs = {
  ...commonStubs,
  // Visual children we don't need to exercise here
  AdminVotingHelpButton: { template: '<div />' },
};

const makeItem = (overrides: Record<string, unknown> = {}) => ({
  id: 'item-1',
  meeting_id: 'm1',
  title: 'Test agenda item',
  order: 1,
  brought_by_students: false,
  type: 'voting',
  votes: [],
  can: { update: true, delete: true },
  ...overrides,
});

describe('MeetingAgendaList', () => {
  it('links every row to the agenda item record', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem()] as App.Entities.AgendaItem[], meetingId: 'm1' },
      global: { stubs: baseStubs },
    });

    const link = wrapper.find('a');
    expect(link.attributes('href')).toContain('agendaItems.show');
    expect(wrapper.text()).toContain('Test agenda item');
  });

  /** Deleting lives in the item's ⋯ now; the list only orders and adds. */
  it('has no delete control, and shows drag handles only while ordering', async () => {
    const wrapper = mount(MeetingAgendaList, {
      props: {
        agendaItems: [makeItem(), makeItem({ id: 'item-2', order: 2 })] as App.Entities.AgendaItem[],
        meetingId: 'm1',
        canReorder: true,
      },
      global: { stubs: baseStubs },
    });

    expect(wrapper.find('[aria-label="Šalinti"]').exists()).toBe(false);
    expect(wrapper.find('.drag-handle').exists()).toBe(false);

    await wrapper.findAll('button').find(button => button.text().includes('Keisti tvarką'))!.trigger('click');

    expect(wrapper.find('.drag-handle').exists()).toBe(true);
    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.text()).toContain('Išsaugoti tvarką');
  });

  /** A compact coloured word, not a boxed badge or a "Trūksta: …" line under every title. */
  it('shows each item\'s status as one compact word', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem({ type: null })] as App.Entities.AgendaItem[], meetingId: 'm1' },
      global: { stubs: baseStubs },
    });

    expect(wrapper.find('[data-slot="agenda-status"]').text()).toBe('Nepažymėtas');
    expect(wrapper.find('[data-slot="status-badge"]').exists()).toBe(false);
  });

  /** A pasted agenda is the usual way an empty meeting gets filled, so it leads. */
  it('offers pasting first, then line-by-line, from the empty state', async () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [] as App.Entities.AgendaItem[], meetingId: 'm1', canAdd: true },
      global: { stubs: baseStubs },
    });

    const buttons = wrapper.findAll('button');
    await buttons.find(button => button.text().includes('meetings.agenda.paste_agenda'))!.trigger('click');
    await buttons.find(button => button.text().includes('meetings.agenda.add_one_by_one'))!.trigger('click');

    expect(wrapper.emitted('add')).toEqual([['paste'], ['lines']]);
  });

  it('offers no add affordance to someone who cannot add', () => {
    const wrapper = mount(MeetingAgendaList, {
      props: { agendaItems: [] as App.Entities.AgendaItem[], meetingId: 'm1' },
      global: { stubs: baseStubs },
    });

    expect(wrapper.text()).not.toContain('meetings.agenda.paste_agenda');
    expect(wrapper.text()).toContain('meetings.agenda.empty_readonly');
  });

  it('shows the vote count label only when there is more than one vote', () => {
    const single = mount(MeetingAgendaList, {
      props: {
        agendaItems: [makeItem({ votes: [{ id: 'v1', is_main: true, decision: 'positive' }] })] as App.Entities.AgendaItem[],
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
        })] as App.Entities.AgendaItem[],
        meetingId: 'm1',
      },
      global: { stubs: baseStubs },
    });
    expect(multiple.text()).toContain('balsavimai');
  });

  /**
   * The marker used to be a coloured dot, which read as a warning on every item the
   * collaborative editor had ever been opened on. `has_notes` now means real content.
   */
  it('marks notes with a neutral icon, only when the item has them', () => {
    const without = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem()] as App.Entities.AgendaItem[], meetingId: 'm1' },
      global: { stubs: baseStubs },
    });
    expect(without.find('[aria-label="Yra pastabų"]').exists()).toBe(false);

    const withNotes = mount(MeetingAgendaList, {
      props: { agendaItems: [makeItem({ has_notes: true })] as App.Entities.AgendaItem[], meetingId: 'm1' },
      global: { stubs: baseStubs },
    });
    expect(withNotes.find('[aria-label="Yra pastabų"]').exists()).toBe(true);
  });

  it('counts a decision-only vote as discussed for an internal body', () => {
    const items = [
      makeItem({ votes: [{ id: 'v1', is_main: true, decision: 'positive' }] }),
      makeItem({ id: 'item-2', order: 2, votes: [] }),
    ];

    const internal = mount(MeetingAgendaList, {
      props: { agendaItems: items as App.Entities.AgendaItem[], meetingId: 'm1', requiresStudentPerspective: false },
      global: { stubs: baseStubs },
    });
    expect(internal.find('[data-slot="agenda-status"]').text()).toBe('Priimtas');

    // External bodies still wait for the student perspective before calling a vote discussed
    const external = mount(MeetingAgendaList, {
      props: { agendaItems: items as App.Entities.AgendaItem[], meetingId: 'm1' },
      global: { stubs: baseStubs },
    });
    expect(external.find('[data-slot="agenda-status"]').text()).not.toBe('Priimtas');
  });
});
