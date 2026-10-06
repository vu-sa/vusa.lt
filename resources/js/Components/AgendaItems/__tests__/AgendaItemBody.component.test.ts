import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemBody from '@/Components/AgendaItems/AgendaItemBody.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

function makeForm(overrides: Record<string, unknown> = {}) {
  return createMockForm({
    title: { lt: 'Test item', en: '' },
    type: 'voting',
    brought_by_students: false,
    student_position: { lt: '', en: '' },
    description: { lt: '', en: '' },
    start_time: null,
    end_time: null,
    votes: [
      { id: '1', is_main: true, is_consensus: false, title: { lt: '', en: '' }, note: { lt: '', en: '' }, student_vote: 'positive', decision: 'positive', student_benefit: 'positive', order: 0 },
    ],
    ...overrides,
  });
}

function factory(props: Record<string, unknown> = {}, formOverrides: Record<string, unknown> = {}) {
  const form = makeForm(formOverrides);
  const wrapper = mount(AgendaItemBody, {
    props: { form, editable: true, ...props },
    global: {
      stubs: {
        ...commonStubs,
        AdminVotingHelpButton: { template: '<div class="help-stub" />' },
        TimePicker: { props: ['modelValue'], template: '<div class="time-picker" />' },
      },
    },
  });
  return { wrapper, form };
}

const typeButton = (wrapper: ReturnType<typeof mount>, value: string) =>
  wrapper.find(`[data-testid="agenda-item-type-${value}"]`);

describe('AgendaItemBody', () => {
  it('offers the four item types, a break included, as big taps', () => {
    const { wrapper } = factory();

    ['voting', 'informational', 'deferred', 'break'].forEach((value) => {
      expect(typeButton(wrapper, value).exists()).toBe(true);
    });
  });

  it('records the type on the form', async () => {
    const { wrapper, form } = factory();

    await typeButton(wrapper, 'break').trigger('click');

    expect(form.type).toBe('break');
  });

  /** Choosing "Balsavimas" should make the outcome the next tap, not an "add a vote" step. */
  it('opens the first vote as soon as the item becomes a vote', async () => {
    const { wrapper, form } = factory({}, { type: null, votes: [] });

    await typeButton(wrapper, 'voting').trigger('click');

    expect(form.votes).toHaveLength(1);
    expect(form.votes[0].is_main).toBe(true);
  });

  it('shows the outcome only for a voting item', () => {
    expect(factory().wrapper.find('#agenda-item-votes').exists()).toBe(true);
    expect(factory({}, { type: 'informational' }).wrapper.find('#agenda-item-votes').exists()).toBe(false);
  });

  it('lets the type picker itself ask for a type, without a hint paragraph', () => {
    const pending = factory({}, { type: null, votes: [] }).wrapper;
    expect(pending.find('[data-testid="agenda-item-type-pending"]').exists()).toBe(true);
    expect(pending.find('[data-slot="form-segmented-control"]').classes()).toContain('border-status-attention-border');

    const chosen = factory({}, { type: 'informational' }).wrapper;
    expect(chosen.find('[data-testid="agenda-item-type-pending"]').exists()).toBe(false);
    expect(chosen.find('[data-slot="form-segmented-control"]').classes()).not.toContain('border-status-attention-border');
  });

  it('lets a chosen type be collapsed, but not an unset one', async () => {
    const { wrapper } = factory();

    await wrapper.find('[data-testid="agenda-item-type-collapse"]').trigger('click');
    expect(wrapper.emitted('update:typeOpen')).toEqual([[false]]);

    const pending = factory({}, { type: null, votes: [] }).wrapper;
    expect(pending.find('[data-testid="agenda-item-type-collapse"]').exists()).toBe(false);
  });

  it('hides the picker while collapsed', () => {
    expect(factory({ typeOpen: false }).wrapper.find('#agenda-item-type').exists()).toBe(false);
  });

  it('reads the type as text for someone who cannot edit', () => {
    const { wrapper } = factory({ editable: false }, { type: 'informational' });

    expect(typeButton(wrapper, 'voting').exists()).toBe(false);
    expect(wrapper.text()).toContain('Informacinis');
  });

  /** Time is a fact of the record, edited in the sheet — not a live control among the taps. */
  it('has no time controls', () => {
    expect(factory().wrapper.find('.time-picker').exists()).toBe(false);
  });
});
