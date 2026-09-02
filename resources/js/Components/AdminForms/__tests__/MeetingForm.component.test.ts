import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import MeetingForm from '@/Components/AdminForms/MeetingForm.vue';
import { commonStubs } from '@/tests/stubs';

const meeting = {
  id: 'meet-1',
  start_time: '2026-05-14 10:00:00',
  type: 'live',
  description: { lt: 'Lietuviškas aprašymas', en: 'English description' },
};

const factory = (props: Record<string, unknown> = {}) =>
  mount(MeetingForm, {
    props: { meeting, ...props },
    global: { stubs: commonStubs },
  });

const localeButton = (wrapper: ReturnType<typeof mount>) =>
  wrapper.findComponent({ name: 'SimpleLocaleButton' });

describe('MeetingForm.vue', () => {
  it('shows the Lithuanian description first', () => {
    const textarea = factory().find('textarea');

    expect((textarea.element as HTMLTextAreaElement).value).toBe('Lietuviškas aprašymas');
  });

  it('swaps the description to English without submitting the form', async () => {
    const wrapper = factory();

    await localeButton(wrapper).vm.$emit('update:locale', 'en');
    await flushPromises();

    expect((wrapper.find('textarea').element as HTMLTextAreaElement).value).toBe('English description');
    // Regression: the toggle used to be a submit button, so flipping the language saved the
    // meeting and closed the dialog it lives in.
    expect(wrapper.emitted('submit')).toBeUndefined();
  });

  it('keeps both locales when only one is edited', async () => {
    const wrapper = factory();

    await wrapper.find('textarea').setValue('Pakeistas aprašymas');
    await wrapper.find('form').trigger('submit');
    // vee-validate resolves validation over two ticks before it emits.
    await flushPromises();
    await flushPromises();

    const submitted = wrapper.emitted('submit')?.[0]?.[0] as { description: { lt: string; en: string } };
    expect(submitted.description).toEqual({ lt: 'Pakeistas aprašymas', en: 'English description' });
  });

  /** Anything reading `toArray()` hands over the localized string instead of the map. */
  it('accepts a plain-string description as the Lithuanian value', () => {
    const wrapper = factory({ meeting: { ...meeting, description: 'Senas formatas' } });

    expect((wrapper.find('textarea').element as HTMLTextAreaElement).value).toBe('Senas formatas');
  });
});
