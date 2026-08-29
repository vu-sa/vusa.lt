import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import EditAgendaItem from '@/Pages/Admin/Representation/EditAgendaItem.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const stubs = {
  AgendaItemNavigator: { template: '<div />' },
  AgendaItemBody: {
    name: 'AgendaItemBody',
    props: ['form', 'editing', 'meetingIsPublic', 'requiresStudentPerspective'],
    template: '<div />',
  },
  AgendaItemNotesSidebar: { template: '<div />' },
  DiscussionPanel: { template: '<div />' },
};

const baseAgendaItem = {
  id: 'item-2',
  meeting_id: 'meet-1',
  title: 'Antras klausimas',
  order: 2,
  brought_by_students: false,
  type: 'voting',
  student_position: null,
  description: null,
  start_time: null,
  end_time: null,
  votes: [],
  meeting: { id: 'meet-1', title: 'Posėdis', institutions: [] },
};

const createWrapper = (props: Record<string, unknown> = {}) =>
  mount(EditAgendaItem, {
    props: {
      agendaItem: baseAgendaItem,
      siblingAgendaItems: [],
      canUpdate: true,
      ...props,
    },
    global: { stubs },
  });

describe('EditAgendaItem.vue', () => {
  it('labels a decision-only vote as decided for an internal body, not "Neaptartas"', () => {
    const wrapper = createWrapper({
      agendaItem: {
        ...baseAgendaItem,
        votes: [{ id: 'v1', is_main: true, is_consensus: false, decision: 'positive', student_vote: null, student_benefit: null, order: 0 }],
      },
      requiresStudentPerspective: false,
    });

    expect(wrapper.text()).toContain('Priimtas');
    expect(wrapper.text()).not.toContain('Neaptartas');
  });

  it('still shows "Neaptartas" for an external body decision without a student vote', () => {
    const wrapper = createWrapper({
      agendaItem: {
        ...baseAgendaItem,
        votes: [{ id: 'v1', is_main: true, is_consensus: false, decision: 'positive', student_vote: null, student_benefit: null, order: 0 }],
      },
    });

    expect(wrapper.text()).toContain('Neaptartas');
  });

  it('leaves start_time empty when there is no previous item with an end time', () => {
    const wrapper = createWrapper();

    const body = wrapper.findComponent({ name: 'AgendaItemBody' });
    expect(body.props('form').start_time).toBeNull();
  });

  /**
   * A one-time default, not a live sync: seeded once from the sibling list this page
   * was loaded with, never re-derived afterward.
   */
  it('defaults start_time from the previous item\'s end time when this item has none yet', () => {
    const wrapper = createWrapper({
      siblingAgendaItems: [
        { id: 'item-1', title: 'Pirmas klausimas', order: 1, brought_by_students: false, start_time: '18:00:00', end_time: '18:30:00' },
        { id: 'item-2', title: 'Antras klausimas', order: 2, brought_by_students: false, start_time: null, end_time: null },
      ],
    });

    const body = wrapper.findComponent({ name: 'AgendaItemBody' });
    expect(body.props('form').start_time).toBe('18:30');
  });

  it('keeps this item\'s own saved start_time rather than the previous item\'s end time', () => {
    const wrapper = createWrapper({
      agendaItem: { ...baseAgendaItem, start_time: '19:00:00' },
      siblingAgendaItems: [
        { id: 'item-1', title: 'Pirmas klausimas', order: 1, brought_by_students: false, start_time: '18:00:00', end_time: '18:30:00' },
        { id: 'item-2', title: 'Antras klausimas', order: 2, brought_by_students: false, start_time: '19:00:00', end_time: null },
      ],
    });

    const body = wrapper.findComponent({ name: 'AgendaItemBody' });
    expect(body.props('form').start_time).toBe('19:00');
  });

  it('ignores a later sibling\'s end time, only the nearest preceding one counts', () => {
    const wrapper = createWrapper({
      agendaItem: { ...baseAgendaItem, id: 'item-2', order: 2 },
      siblingAgendaItems: [
        { id: 'item-1', title: 'Pirmas', order: 1, brought_by_students: false, start_time: null, end_time: '18:30:00' },
        { id: 'item-2', title: 'Antras', order: 2, brought_by_students: false, start_time: null, end_time: null },
        { id: 'item-3', title: 'Trečias', order: 3, brought_by_students: false, start_time: null, end_time: '20:00:00' },
      ],
    });

    const body = wrapper.findComponent({ name: 'AgendaItemBody' });
    expect(body.props('form').start_time).toBe('18:30');
  });
});
