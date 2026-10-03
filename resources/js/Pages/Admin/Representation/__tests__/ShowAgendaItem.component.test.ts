import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { router } from '@inertiajs/vue3';

import ShowAgendaItem from '@/Pages/Admin/Representation/ShowAgendaItem.vue';
import { commonStubs } from '@/tests/stubs';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string, params?: Record<string, string>) => (name === undefined
  ? { current: () => false }
  : `/mocked/${name}${params ? `?${new URLSearchParams(params).toString()}` : ''}`));

const stubs = {
  ...commonStubs,
  AgendaItemNotesSidebar: { template: '<div data-testid="notes" />' },
  AgendaItemSheetForm: { name: 'AgendaItemSheetForm', props: ['open', 'form', 'canDelete', 'defaultStartTime'], template: '<div data-testid="sheet" :data-open="open" />' },
  AgendaItemVotesSheetForm: { name: 'AgendaItemVotesSheetForm', props: ['open', 'form'], template: '<div data-testid="votes-sheet" :data-open="open" />' },
  RecordActivity: { template: '<div data-testid="activity" />' },
  AdminVotingHelpButton: { template: '<div />' },
  TimePicker: { props: ['modelValue'], template: '<div />' },
};

const vote = (overrides: Record<string, unknown> = {}) => ({
  id: 'v1',
  is_main: true,
  is_consensus: false,
  title: { lt: '', en: '' },
  note: { lt: '', en: '' },
  decision: null,
  student_vote: null,
  student_benefit: null,
  order: 0,
  ...overrides,
});

const baseAgendaItem = {
  id: 'item-2',
  meeting_id: 'meet-1',
  title: { lt: 'Antras klausimas', en: '' },
  order: 2,
  brought_by_students: false,
  type: 'voting',
  student_position: { lt: '', en: '' },
  description: { lt: 'Aprašymas', en: '' },
  start_time: null,
  end_time: null,
  votes: [vote()],
  meeting: { id: 'meet-1', title: 'Posėdis', start_time: '2026-09-24T10:00:00Z', institutions: [{ id: 'i1', name: 'VU Senatas' }] },
};

const sibling = (id: string, order: number, missing: unknown = null, extra: Record<string, unknown> = {}) => ({
  id,
  title: `Klausimas ${order}`,
  order,
  brought_by_students: false,
  start_time: null,
  end_time: null,
  missing,
  ...extra,
});

const missing = (id: string, position: number) => ({ type: 'agenda_item_vote_missing', agenda_item_id: id, title: 'x', position, missing_fields: ['decision'] });

const createWrapper = (props: Record<string, unknown> = {}) =>
  mount(ShowAgendaItem, {
    props: {
      agendaItem: baseAgendaItem,
      siblingAgendaItems: [sibling('item-1', 1), sibling('item-2', 2), sibling('item-3', 3)],
      abilities: { update: true, delete: true },
      ...props,
    },
    global: { stubs },
  });

describe('ShowAgendaItem.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    window.history.replaceState({}, '', '/');
  });

  it('records an outcome with one tap, without an edit mode', async () => {
    const wrapper = createWrapper();

    expect(wrapper.text()).not.toContain('REDAGUOJI');
    await wrapper.find('[data-testid="vote-decision-positive"]').trigger('click');

    // The mocked useForm is not reactive, so assert on what autosave will send.
    const form = wrapper.findComponent({ name: 'AgendaItemBody' }).props('form') as { votes: Array<{ decision: string | null }> };
    expect(form.votes[0]!.decision).toBe('positive');
  });

  it('reads the same layout as badges for someone who cannot edit', () => {
    const wrapper = createWrapper({ abilities: { update: false, delete: false } });

    expect(wrapper.find('[data-testid="vote-decision-positive"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('Nefiksuota');
    expect(wrapper.text()).toContain('meetings.item.view_only');
    expect(wrapper.find('[data-testid="sheet"]').exists()).toBe(false);
  });

  it('folds a set type into a line under the facts and reopens it on demand', async () => {
    const wrapper = createWrapper();

    expect(wrapper.find('#agenda-item-type').exists()).toBe(false);
    await wrapper.find('[data-testid="agenda-item-change-type"]').trigger('click');

    expect(wrapper.find('[data-testid="agenda-item-type-voting"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="agenda-item-change-type"]').exists()).toBe(false);
  });

  it('keeps asking for the type while it is unset', () => {
    const wrapper = createWrapper({ agendaItem: { ...baseAgendaItem, type: null, votes: [] } });

    expect(wrapper.find('[data-testid="agenda-item-type-voting"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="agenda-item-change-type"]').exists()).toBe(false);
  });

  it('opens the text sheet from the primary action', async () => {
    const wrapper = createWrapper();

    await wrapper.findAll('button').find(button => button.text().includes('meetings.item.edit'))!.trigger('click');

    expect(wrapper.find('[data-testid="sheet"]').attributes('data-open')).toBe('true');
  });

  it('keeps a single edit entry point, in the header', () => {
    const wrapper = createWrapper();

    expect(wrapper.findAll('button').filter(button => button.text().includes('meetings.item.edit'))).toHaveLength(1);
  });

  it('opens the votes sheet from the vote list', async () => {
    const wrapper = createWrapper();

    await wrapper.findAll('button').find(button => button.text().includes('meetings.item.manage_votes'))!.trigger('click');

    expect(wrapper.find('[data-testid="votes-sheet"]').attributes('data-open')).toBe('true');
  });

  it('lists the institution before the meeting, and "raised by" only for a students\' question', () => {
    const labels = (wrapper: ReturnType<typeof createWrapper>) => wrapper.findAll('dt').map(term => term.text());

    const plain = labels(createWrapper());
    expect(plain.indexOf('meetings.item.institution')).toBeLessThan(plain.indexOf('meetings.item.meeting'));
    expect(plain).not.toContain('meetings.item.raised_by');

    expect(labels(createWrapper({ agendaItem: { ...baseAgendaItem, brought_by_students: true } }))).toContain('meetings.item.raised_by');
  });

  it('shows public visibility after status and only once outside the description', () => {
    const wrapper = createWrapper({
      agendaItem: { ...baseAgendaItem, meeting: { ...baseAgendaItem.meeting, is_public: true } },
      publicUrl: 'https://www.vusa.test/lt/meeting',
    });
    const visibility = wrapper.findAll('dl > div')[1];
    const link = visibility.find('a');

    expect(visibility.classes()).toContain('bg-status-success-surface');
    expect(visibility.find('dt').attributes('aria-label')).toBe('Matomumas');
    expect(visibility.find('dt svg').exists()).toBe(true);
    expect(visibility.find('dt').text()).toBe('');
    expect(link.text()).toBe('meetings.record.visible_public');
    expect(link.attributes('href')).toBe('https://www.vusa.test/lt/meeting');
    expect(link.attributes('target')).toBe('_blank');
    expect(link.attributes('rel')).toBe('noopener noreferrer');
    expect(link.find('svg').exists()).toBe(true);
    expect(wrapper.find('#agenda-item-description-title').text()).toBe('meetings.item.description');
  });

  it('uses a neutral visibility surface when the meeting is internal', () => {
    const visibility = createWrapper().findAll('dl > div')[1];

    expect(visibility.classes()).toContain('bg-status-neutral-surface');
    expect(visibility.find('dd').text()).toBe('meetings.record.internal_only');
    expect(visibility.find('a').exists()).toBe(false);
  });

  it('labels a decision-only vote as decided for an internal body, not "Neaptartas"', () => {
    const wrapper = createWrapper({
      agendaItem: { ...baseAgendaItem, votes: [vote({ decision: 'positive' })] },
      requiresStudentPerspective: false,
    });

    expect(wrapper.find('dl > div dd').text()).toBe('Priimtas');
  });

  it('still shows "Neaptartas" for an external body decision without a student vote', () => {
    const wrapper = createWrapper({
      agendaItem: { ...baseAgendaItem, votes: [vote({ decision: 'positive' })] },
    });

    expect(wrapper.find('dl > div dd').text()).toBe('Neaptartas');
  });

  describe('start time suggestion', () => {
    const suggestion = (wrapper: ReturnType<typeof mount>) =>
      wrapper.findComponent({ name: 'AgendaItemSheetForm' }).props('defaultStartTime');

    it('suggests the nearest preceding item\'s end time in the sheet, not in the saved facts', () => {
      const wrapper = createWrapper({
        siblingAgendaItems: [
          sibling('item-1', 1, null, { end_time: '18:30:00' }),
          sibling('item-2', 2),
          sibling('item-3', 3, null, { end_time: '20:00:00' }),
        ],
      });

      expect(suggestion(wrapper)).toBe('18:30');
      expect(wrapper.find('dl').text()).not.toContain('18:30');
    });

    it('shows the saved time range among the facts', () => {
      const wrapper = createWrapper({ agendaItem: { ...baseAgendaItem, start_time: '19:00:00', end_time: '19:30:00' } });

      expect(wrapper.find('dl').text()).toContain('19:00–19:30');
    });
  });

  describe('navigation', () => {
    it('steps through every item by default', () => {
      const wrapper = createWrapper();

      expect(wrapper.find('[data-testid="agenda-item-position"]').text()).toBe('2 / 3');
    });

    it('steps through every item, incomplete or not, even from an old walk-through link', async () => {
      window.history.replaceState({}, '', '/?walk=missing');
      const wrapper = createWrapper({
        siblingAgendaItems: [
          sibling('item-1', 1, missing('item-1', 1)),
          sibling('item-2', 2, missing('item-2', 2)),
          sibling('item-3', 3),
        ],
      });

      expect(wrapper.find('[data-testid="agenda-item-position"]').text()).toBe('2 / 3');

      await wrapper.find('[aria-label="Kitas įrašas"]').trigger('click');

      expect(router.visit).toHaveBeenCalledWith('/mocked/agendaItems.show?agendaItem=item-3');
    });
  });

  it('leads the facts with the status, toned in its role', () => {
    const wrapper = createWrapper();
    const first = wrapper.find('dl > div');

    expect(first.find('dt').text()).toBe('Būsena');
    expect(first.attributes('data-status-role')).toBeTruthy();
    expect(wrapper.find('header [data-slot="status-badge"]').exists()).toBe(false);
  });

  it('builds the title from the Lithuanian text, not the translation map', () => {
    const wrapper = createWrapper({
      agendaItem: { ...baseAgendaItem, title: { lt: 'Stipendijos', en: 'Scholarships' } },
    });

    expect(wrapper.find('h1').text()).toBe('Stipendijos');
  });

  it('accepts a plain-string title as the Lithuanian translation', () => {
    const wrapper = createWrapper({ agendaItem: { ...baseAgendaItem, title: 'Senas klausimas' } });

    expect(wrapper.find('h1').text()).toBe('Senas klausimas');
  });
});
