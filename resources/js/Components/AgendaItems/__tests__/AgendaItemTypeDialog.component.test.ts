import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';

import AgendaItemTypeDialog from '@/Components/AgendaItems/AgendaItemTypeDialog.vue';
import { createMockForm } from '@/tests/helpers/createMockForm';
import { commonStubs } from '@/tests/stubs';

function factory(formOverrides: Record<string, unknown> = {}, meeting?: { start_time?: string | null; type?: string | null }) {
  const form = createMockForm({ type: 'informational', votes: [], ...formOverrides });
  const wrapper = mount(AgendaItemTypeDialog, {
    props: { open: true, form, meeting },
    global: { stubs: { ...commonStubs, VisuallyHidden: { template: '<span><slot /></span>' } } },
  });

  return { wrapper, form };
}

const choice = (wrapper: ReturnType<typeof mount>, value: string) => wrapper.get(`[data-testid="agenda-item-type-${value}"]`);

describe('AgendaItemTypeDialog', () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  it('offers the five item types, withdrawn and break included, and marks the current one', () => {
    const { wrapper } = factory();

    ['voting', 'informational', 'deferred', 'withdrawn', 'break'].forEach(value => expect(choice(wrapper, value).exists()).toBe(true));
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

  it.each([
    ['2026-10-01T10:00:00Z', 'in-person', 'meeting_past'],
    ['2026-11-01T10:00:00Z', 'remote', 'meeting_upcoming'],
    [null, null, 'meeting_undated'],
    ['2026-10-01T10:00:00Z', 'email', 'email_past'],
    ['2026-11-01T10:00:00Z', 'email', 'email_upcoming'],
    [null, 'email', 'email_undated'],
  ])('asks about a meeting at %s of type %s in the right tense', (startTime, type, question) => {
    // Asserts the key: admin strings come from the build-generated lang/php_*.json.
    vi.useFakeTimers({ now: new Date('2026-10-20T12:00:00Z') });

    const { wrapper } = factory({}, { start_time: startTime, type });

    expect(wrapper.text()).toContain(`meetings.item.type_question.${question}`);
  });

  it('asks the undated question when the meeting is unknown', () => {
    expect(factory().wrapper.text()).toContain('meetings.item.type_question.meeting_undated');
  });
});
