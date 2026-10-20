import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import AgendaItemTypeDialog from '@/Components/AgendaItems/AgendaItemTypeDialog.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

function factory(formOverrides: Record<string, unknown> = {}) {
  const form = createMockForm({ type: 'informational', votes: [], ...formOverrides });
  const wrapper = mount(AgendaItemTypeDialog, {
    props: { open: true, form },
    global: { stubs: { ...commonStubs, VisuallyHidden: { template: '<span><slot /></span>' } } },
  });

  return { wrapper, form };
}

const choice = (wrapper: ReturnType<typeof mount>, value: string) => wrapper.get(`[data-testid="agenda-item-type-${value}"]`);

describe('AgendaItemTypeDialog', () => {
  it('offers the four item types, a break included, and marks the current one', () => {
    const { wrapper } = factory();

    ['voting', 'informational', 'deferred', 'break'].forEach(value => expect(choice(wrapper, value).exists()).toBe(true));
    expect(choice(wrapper, 'informational').attributes('aria-pressed')).toBe('true');
    expect(choice(wrapper, 'voting').attributes('aria-pressed')).toBeUndefined();
  });

  it('records the choice and closes, since choosing is the whole answer', async () => {
    const { wrapper, form } = factory();

    await choice(wrapper, 'break').trigger('click');

    expect(form.type).toBe('break');
    expect(wrapper.emitted('update:open')).toEqual([[false]]);
  });

  /** Choosing "Balsavimas" should make the outcome the next tap, not an "add a vote" step. */
  it('opens the first vote as soon as the item becomes a vote', async () => {
    const { wrapper, form } = factory({ type: null });

    await choice(wrapper, 'voting').trigger('click');

    expect(form.votes).toHaveLength(1);
    expect(form.votes[0].is_main).toBe(false);
  });
});
