import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import EditAgendaItem from '@/Pages/Admin/Representation/EditAgendaItem.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

vi.stubGlobal('route', (name?: string) => (name === undefined ? { current: () => false } : `/mocked/${name}`));

const stubs = {
  AgendaItemNavigator: { template: '<div />' },
  AgendaItemBody: {
    name: 'AgendaItemBody',
    props: ['form', 'editing', 'locale', 'requiresStudentPerspective'],
    template: '<div />',
  },
  AgendaItemNotesSidebar: { template: '<div />' },
  DiscussionPanel: { template: '<div />' },
  // Declares `title` so the test can read what the page hands the document head.
  InertiaHead: { name: 'InertiaHead', props: ['title'], template: '<div />' },
};

const baseAgendaItem = {
  id: 'item-2',
  meeting_id: 'meet-1',
  title: { lt: 'Antras klausimas', en: '' },
  order: 2,
  brought_by_students: false,
  type: 'voting',
  student_position: { lt: '', en: '' },
  description: { lt: '', en: '' },
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
        votes: [{ id: 'v1', is_main: true, is_consensus: false, title: { lt: '', en: '' }, note: { lt: '', en: '' }, decision: 'positive', student_vote: null, student_benefit: null, order: 0 }],
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
        votes: [{ id: 'v1', is_main: true, is_consensus: false, title: { lt: '', en: '' }, note: { lt: '', en: '' }, decision: 'positive', student_vote: null, student_benefit: null, order: 0 }],
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

  /**
   * Bilingual agenda items are rare, so the language switch must not intrude on the common
   * case. These cover when it appears and what it rebinds — not how it looks.
   */
  describe('English translations', () => {
    const localeButton = (wrapper: ReturnType<typeof mount>) =>
      wrapper.findAllComponents({ name: 'SimpleLocaleButton' });

    /**
     * The switch is kept mounted and hidden with `visibility` rather than `v-if`, so that
     * turning edit mode on does not shift the header. Presence and visibility differ here.
     */
    const localeButtonVisible = (wrapper: ReturnType<typeof mount>) => {
      const button = localeButton(wrapper);
      if (button.length === 0) {
        return false;
      }

      return !button[0].element.closest('.invisible');
    };

    it('hides the language switch in read mode', () => {
      // `type` is set, so the page opens read-first rather than in edit mode.
      const wrapper = createWrapper();

      expect(localeButtonVisible(wrapper)).toBe(false);
    });

    it('hides the language switch in read mode even for an already-translated item', () => {
      // Entering a translation is all the switch is for; the read view is always Lithuanian.
      const wrapper = createWrapper({
        agendaItem: { ...baseAgendaItem, title: { lt: 'Antras klausimas', en: 'Second item' } },
      });

      expect(localeButtonVisible(wrapper)).toBe(false);
      expect(wrapper.text()).toContain('Antras klausimas');
      expect(wrapper.text()).not.toContain('Second item');
    });

    it('keeps the hidden switch mounted so toggling edit mode does not shift the header', async () => {
      const wrapper = createWrapper();

      expect(localeButton(wrapper)).toHaveLength(1);

      await wrapper.findComponent({ name: 'Switch' }).vm.$emit('update:modelValue', true);

      // Same node throughout — only its visibility changes.
      expect(localeButton(wrapper)).toHaveLength(1);
      expect(localeButtonVisible(wrapper)).toBe(true);
    });

    it('never offers the language switch to someone who cannot edit', () => {
      const wrapper = createWrapper({
        agendaItem: { ...baseAgendaItem, type: null, title: { lt: 'Antras klausimas', en: 'Second item' } },
        canUpdate: false,
      });

      // Nothing is reserved either, so a read-only viewer sees no phantom gap.
      expect(localeButton(wrapper)).toHaveLength(0);
    });

    it('offers the language switch once the item is being edited', () => {
      const wrapper = createWrapper({ agendaItem: { ...baseAgendaItem, type: null } });

      expect(localeButtonVisible(wrapper)).toBe(true);
    });

    it('seeds the form with both locales and starts on Lithuanian', () => {
      const wrapper = createWrapper({
        agendaItem: { ...baseAgendaItem, title: { lt: 'Antras klausimas', en: 'Second item' } },
      });

      const body = wrapper.findComponent({ name: 'AgendaItemBody' });
      expect(body.props('form').title).toEqual({ lt: 'Antras klausimas', en: 'Second item' });
      expect(body.props('locale')).toBe('lt');
    });

    it('switches every field to English at once', async () => {
      const wrapper = createWrapper({
        agendaItem: { ...baseAgendaItem, type: null, title: { lt: 'Antras klausimas', en: 'Second item' } },
      });

      await localeButton(wrapper)[0].vm.$emit('update:locale', 'en');

      // One switch drives the title, the body and the votes together.
      expect(wrapper.findComponent({ name: 'AgendaItemBody' }).props('locale')).toBe('en');
      // `trans` is stubbed to the identity in tests/setup.ts, so the key is what renders.
      expect(wrapper.text()).toContain('meetings.agenda.editing_english');
    });

    it('drops back to Lithuanian when edit mode is switched off', async () => {
      const wrapper = createWrapper({
        agendaItem: { ...baseAgendaItem, type: null, title: { lt: 'Antras klausimas', en: 'Second item' } },
      });

      await localeButton(wrapper)[0].vm.$emit('update:locale', 'en');
      expect(wrapper.findComponent({ name: 'AgendaItemBody' }).props('locale')).toBe('en');

      // Otherwise the next edit would silently resume in English.
      await wrapper.findComponent({ name: 'Switch' }).vm.$emit('update:modelValue', false);

      expect(wrapper.findComponent({ name: 'AgendaItemBody' }).props('locale')).toBe('lt');
      expect(localeButtonVisible(wrapper)).toBe(false);
      expect(wrapper.text()).toContain('Antras klausimas');
    });

    it('builds the browser title from the Lithuanian text, not the translation map', () => {
      // Regression: interpolating the `{lt, en}` prop rendered "Redaguoti: [object Object]".
      const wrapper = createWrapper({
        agendaItem: { ...baseAgendaItem, title: { lt: 'Antras klausimas', en: 'Second item' } },
      });

      const head = wrapper.findComponent({ name: 'InertiaHead' });
      expect(head.props('title')).toBe('Redaguoti: Antras klausimas');
      expect(head.props('title')).not.toContain('[object Object]');
    });

    /** Coerces the legacy flat string a stale payload could still carry. */
    it('accepts a plain-string title as the Lithuanian translation', () => {
      const wrapper = createWrapper({
        agendaItem: { ...baseAgendaItem, title: 'Senas formatas' },
      });

      const body = wrapper.findComponent({ name: 'AgendaItemBody' });
      expect(body.props('form').title).toEqual({ lt: 'Senas formatas', en: '' });
    });
  });
});
