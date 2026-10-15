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
  it('leaves choosing the type to the record page for an editor', () => {
    const { wrapper } = factory();

    expect(wrapper.find('#agenda-item-type').exists()).toBe(false);
    expect(typeButton(wrapper, 'voting').exists()).toBe(false);
  });

  it('shows the outcome only for a voting item', () => {
    expect(factory().wrapper.find('#agenda-item-votes').exists()).toBe(true);
    expect(factory({}, { type: 'informational' }).wrapper.find('#agenda-item-votes').exists()).toBe(false);
  });

  it('reads the type as text for someone who cannot edit', () => {
    const { wrapper } = factory({ editable: false }, { type: 'informational' });

    expect(wrapper.get('#agenda-item-type').text()).toContain('Informacinis');
  });

  /** Time is a fact of the record, edited in the sheet — not a live control among the taps. */
  it('has no time controls', () => {
    expect(factory().wrapper.find('.time-picker').exists()).toBe(false);
  });
});
