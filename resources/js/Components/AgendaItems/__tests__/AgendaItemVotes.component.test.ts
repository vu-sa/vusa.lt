import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemVotes from '@/Components/AgendaItems/AgendaItemVotes.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

/** Votes carry translatable `title`/`note` — see EditableVote in useAgendaItemAutosave. */
const recordedVote = (overrides: Record<string, unknown> = {}) => ({
  id: '1',
  is_main: true,
  is_consensus: false,
  title: { lt: '', en: '' },
  note: { lt: '', en: '' },
  student_vote: 'positive',
  decision: 'positive',
  student_benefit: 'positive',
  order: 0,
  ...overrides,
});

const emptyVote = (overrides: Record<string, unknown> = {}) => recordedVote({
  id: '2',
  is_main: false,
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
    props: { form, editable: true, ...props },
    global: { stubs: commonStubs },
  });
  return { wrapper, form };
}

const buttonWithText = (wrapper: ReturnType<typeof mount>, text: string) =>
  wrapper.findAll('button').find(button => button.text().includes(text));

describe('AgendaItemVotes', () => {
  it('records an answer with one tap', async () => {
    const { wrapper, form } = factory({ votes: [emptyVote({ is_main: true })] });

    await wrapper.find('[data-testid="vote-decision-negative"]').trigger('click');

    expect(form.votes[0].decision).toBe('negative');
  });

  it('collapses an answered row to its answer, and reopens it to change it', async () => {
    const { wrapper, form } = factory();

    expect(wrapper.find('[data-testid="vote-decision-negative"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="vote-decision-answer"]').text()).toContain('Priimtas');

    await wrapper.find('[data-testid="vote-decision-answer"]').trigger('click');
    await wrapper.find('[data-testid="vote-decision-negative"]').trigger('click');

    expect(form.votes[0].decision).toBe('negative');
    expect(wrapper.find('[data-testid="vote-decision-negative"]').exists()).toBe(false);
  });

  it('can take an answer back to "not recorded"', async () => {
    const { wrapper, form } = factory();

    await wrapper.find('[data-testid="vote-decision-answer"]').trigger('click');
    await wrapper.find('[data-testid="vote-decision-clear"]').trigger('click');

    expect(form.votes[0].decision).toBeNull();
    expect(wrapper.find('[data-testid="vote-decision-positive"]').exists()).toBe(true);
  });

  it('shows a vote title as text, leaving its editing to the votes sheet', () => {
    const { wrapper } = factory({ votes: [recordedVote({ title: { lt: 'Dėl biudžeto', en: '' } })] });

    expect(wrapper.text()).toContain('Dėl biudžeto');
    expect(wrapper.find('input').exists()).toBe(false);
  });

  it('reads each answer as a status badge for someone who cannot edit', () => {
    const { wrapper } = factory({ editable: false, votes: [emptyVote({ is_main: true, decision: 'positive' })] });

    expect(wrapper.find('[data-testid="vote-decision-positive"]').exists()).toBe(false);
    expect(wrapper.findAll('[data-slot="status-badge"]').map(badge => badge.text())).toEqual(['Priimtas', 'Nebalsuota', 'Nežinoma']);
    expect(buttonWithText(wrapper, 'meetings.item.add_vote')).toBeUndefined();
  });

  describe('several votes', () => {
    it('keeps ordering, main and removal out of the cards', () => {
      const { wrapper } = factory({ votes: [recordedVote(), emptyVote()] });

      expect(wrapper.find('[aria-label="Perkelti žemyn"]').exists()).toBe(false);
      expect(wrapper.find('[aria-label="meetings.item.remove_vote"]').exists()).toBe(false);
      expect(buttonWithText(wrapper, 'meetings.item.make_main')).toBeUndefined();
    });

    it('asks the page to open the votes sheet', async () => {
      const { wrapper } = factory();

      await buttonWithText(wrapper, 'meetings.item.manage_votes')!.trigger('click');

      expect(wrapper.emitted('manage')).toHaveLength(1);
    });

    it('adds a vote that is not the main one', async () => {
      const { wrapper, form } = factory();

      await buttonWithText(wrapper, 'meetings.item.add_vote')!.trigger('click');

      expect(form.votes).toHaveLength(2);
      expect(form.votes[1].is_main).toBe(false);
    });
  });

  describe('consensus', () => {
    it('switches off once the students\' vote parts from the decision', async () => {
      const { wrapper, form } = factory({ votes: [recordedVote({ is_consensus: true })] });

      await wrapper.find('[data-testid="vote-student_vote-answer"]').trigger('click');
      await wrapper.find('[data-testid="vote-student_vote-negative"]').trigger('click');

      expect(form.votes[0]).toMatchObject({ is_consensus: false, student_vote: 'negative' });
    });

    it('switches off once the decision is no longer adopted', async () => {
      const { wrapper, form } = factory({ votes: [recordedVote({ is_consensus: true })] });

      await wrapper.find('[data-testid="vote-decision-answer"]').trigger('click');
      await wrapper.find('[data-testid="vote-decision-neutral"]').trigger('click');

      expect(form.votes[0].is_consensus).toBe(false);
    });

    it('survives a change to the student benefit alone', async () => {
      const { wrapper, form } = factory({ votes: [recordedVote({ is_consensus: true })] });

      await wrapper.find('[data-testid="vote-student_benefit-answer"]').trigger('click');
      await wrapper.find('[data-testid="vote-student_benefit-neutral"]').trigger('click');

      expect(form.votes[0].is_consensus).toBe(true);
    });
  });

  describe('governance scope', () => {
    it('records the student position and benefit for an external body', () => {
      const { wrapper } = factory();

      expect(wrapper.find('[data-testid="vote-row-student_vote"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="vote-row-student_benefit"]').exists()).toBe(true);
    });

    it('asks a VU SA body only for the outcome', () => {
      const { wrapper } = factory({ requiresStudentPerspective: false });

      expect(wrapper.find('[data-testid="vote-row-decision"]').exists()).toBe(true);
      expect(wrapper.find('[data-testid="vote-row-student_vote"]').exists()).toBe(false);
    });

    it('consensus only sets the outcome for a VU SA body', async () => {
      const { wrapper, form } = factory({ requiresStudentPerspective: false, votes: [emptyVote({ is_main: true })] });

      await wrapper.findComponent({ name: 'Switch' }).vm.$emit('update:modelValue', true);

      expect(form.votes[0]).toMatchObject({ is_consensus: true, decision: 'positive', student_vote: null });
    });
  });
});
