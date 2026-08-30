import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemVotes from '@/Components/AgendaItems/AgendaItemVotes.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

const recordedVote = (overrides: Record<string, unknown> = {}) => ({
  id: '1',
  is_main: true,
  is_consensus: false,
  title: null,
  student_vote: 'positive',
  decision: 'positive',
  student_benefit: 'positive',
  order: 0,
  ...overrides,
});

const emptyVote = (overrides: Record<string, unknown> = {}) => ({
  id: '2',
  is_main: false,
  is_consensus: false,
  title: null,
  student_vote: null,
  decision: null,
  student_benefit: null,
  order: 1,
  ...overrides,
});

function factory(props: Record<string, unknown> = {}) {
  const form = createMockForm({
    type: 'voting',
    votes: (props.votes as unknown[]) ?? [recordedVote()],
  });
  const wrapper = mount(AgendaItemVotes, {
    props: { form, editing: false, ...props },
    global: {
      stubs: {
        ...commonStubs,
        AdminVotingHelpButton: { template: '<div class="help-stub" />' },
      },
    },
  });
  return { wrapper, form };
}

const buttonWithTitle = (wrapper: ReturnType<typeof mount>, title: string) =>
  wrapper.findAll('button').filter(b => b.attributes('title') === title);

describe('AgendaItemVotes', () => {
  describe('collapsing', () => {
    it('summarises a fully recorded vote instead of expanding it', () => {
      const { wrapper } = factory();

      expect(wrapper.text()).toContain('Priimtas');
      expect(wrapper.text()).toContain('Pritarė');
      expect(wrapper.text()).toContain('Palanku');
      // The label→options grid stays closed while nothing is missing.
      expect(wrapper.text()).not.toContain('Rezultatas');
    });

    it('opens a vote that still misses an answer', () => {
      const { wrapper } = factory({ votes: [emptyVote({ is_main: true })] });
      expect(wrapper.text()).toContain('Rezultatas');
    });

    it('expands and collapses on the header toggle', async () => {
      const { wrapper } = factory();
      const toggle = wrapper.find('button[aria-expanded]');

      expect(toggle.attributes('aria-expanded')).toBe('false');
      await toggle.trigger('click');
      expect(wrapper.text()).toContain('Rezultatas');

      await wrapper.find('button[aria-expanded]').trigger('click');
      expect(wrapper.text()).not.toContain('Rezultatas');
    });
  });

  describe('main vote', () => {
    it('marks another vote as main by hand', async () => {
      const { wrapper, form } = factory({
        editing: true,
        votes: [recordedVote(), recordedVote({ id: '2', is_main: false })],
      });

      await buttonWithTitle(wrapper, 'Žymėti pagrindiniu')[0].trigger('click');

      expect(form.votes[0].is_main).toBe(false);
      expect(form.votes[1].is_main).toBe(true);
    });

    it('does not offer main selection in view mode', () => {
      const { wrapper } = factory({
        editing: false,
        votes: [recordedVote(), recordedVote({ id: '2', is_main: false })],
      });

      expect(buttonWithTitle(wrapper, 'Žymėti pagrindiniu').every(b => b.attributes('disabled') !== undefined)).toBe(true);
    });
  });

  describe('reordering', () => {
    it('shows drag handles only while editing more than one vote', () => {
      expect(factory({ editing: true, votes: [recordedVote()] }).wrapper.find('.vote-drag-handle').exists()).toBe(false);
      expect(factory({ editing: false, votes: [recordedVote(), recordedVote({ id: '2', is_main: false })] })
        .wrapper.find('.vote-drag-handle').exists()).toBe(false);
      expect(factory({ editing: true, votes: [recordedVote(), recordedVote({ id: '2', is_main: false })] })
        .wrapper.find('.vote-drag-handle').exists()).toBe(true);
    });
  });

  describe('editing', () => {
    it('adds a voting question', async () => {
      const { wrapper, form } = factory({ editing: true });
      const addButton = wrapper.findAll('button').find(b => b.text().includes('Pridėti balsavimo klausimą'));

      await addButton!.trigger('click');

      expect(form.votes).toHaveLength(2);
    });

    it('removing the main vote promotes the next one', async () => {
      const { wrapper, form } = factory({
        editing: true,
        votes: [emptyVote({ id: '1', is_main: true }), emptyVote({ id: '2' })],
      });

      await buttonWithTitle(wrapper, 'Šalinti balsavimą')[0].trigger('click');

      expect(form.votes).toHaveLength(1);
      expect(form.votes[0].id).toBe('2');
      expect(form.votes[0].is_main).toBe(true);
    });

    it('leaves a voting item with no votes at all when the last one is removed', async () => {
      const { wrapper, form } = factory({ editing: true, votes: [emptyVote({ is_main: true })] });

      await buttonWithTitle(wrapper, 'Šalinti balsavimą')[0].trigger('click');

      expect(form.votes).toHaveLength(0);
    });

    it('does not render an add-vote button in view mode', () => {
      const { wrapper } = factory({ editing: false });
      expect(wrapper.findAll('button').some(b => b.text().includes('Pridėti balsavimo klausimą'))).toBe(false);
    });

    /** Same markup in both modes, so the page does not shift when the toggle flips. */
    it('locks the value buttons in view mode rather than replacing them', () => {
      const editingButton = factory({ editing: true, votes: [emptyVote({ is_main: true })] })
        .wrapper.findAll('button').find(b => b.text() === 'Atmestas');
      const lockedButton = factory({ editing: false, votes: [emptyVote({ is_main: true })] })
        .wrapper.findAll('button').find(b => b.text() === 'Atmestas');

      expect(editingButton!.attributes('disabled')).toBeUndefined();
      expect(lockedButton!.attributes('disabled')).toBeDefined();
    });

    it('shows a "not discussed" state for a voting item with no votes', () => {
      const { wrapper } = factory({ editing: false, votes: [] });
      expect(wrapper.text()).toContain('Neaptarta');
    });
  });

  describe('governance scope', () => {
    it('records the student position for an external body', () => {
      const { wrapper } = factory({ requiresStudentPerspective: true, votes: [emptyVote({ is_main: true })] });
      expect(wrapper.text()).toContain('Rezultatas');
      expect(wrapper.text()).toContain('Studentai');
      expect(wrapper.text()).toContain('Nauda');
    });

    it('asks a VU SA body only for the outcome', () => {
      const { wrapper } = factory({ requiresStudentPerspective: false, votes: [emptyVote({ is_main: true })] });
      expect(wrapper.text()).toContain('Rezultatas');
      expect(wrapper.text()).not.toContain('Studentai');
      expect(wrapper.text()).not.toContain('Nauda');
    });

    it('consensus only sets the outcome for a VU SA body', async () => {
      const { wrapper, form } = factory({
        editing: true,
        requiresStudentPerspective: false,
        votes: [emptyVote({ is_main: true })],
      });

      const switches = wrapper.findAllComponents({ name: 'Switch' });
      await switches[switches.length - 1].vm.$emit('update:modelValue', true);

      expect(form.votes[0].decision).toBe('positive');
      expect(form.votes[0].student_vote).toBeNull();
      expect(form.votes[0].student_benefit).toBeNull();
    });
  });
});
