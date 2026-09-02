import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import SimpleLocaleButton from '@/Components/Buttons/SimpleLocaleButton.vue';
import { commonStubs } from '@/tests/stubs';

const factory = (locale: 'lt' | 'en' = 'lt') =>
  mount(SimpleLocaleButton, {
    props: { locale },
    global: { stubs: commonStubs },
  });

describe('SimpleLocaleButton.vue', () => {
  /**
   * Regression: a bare <button> defaults to type="submit". Every admin form that offers this
   * toggle (CalendarForm, TypeForm, DutyForm, InstitutionForm, MeetingForm, MultiLocaleInput…)
   * renders it inside a <form>, so flipping the language submitted and closed the form.
   */
  it('is not a submit button', () => {
    expect(factory().find('button').attributes('type')).toBe('button');
  });

  it('emits the opposite locale in each direction', async () => {
    const lt = factory('lt');
    await lt.find('button').trigger('click');
    expect(lt.emitted('update:locale')?.[0]).toEqual(['en']);

    const en = factory('en');
    await en.find('button').trigger('click');
    expect(en.emitted('update:locale')?.[0]).toEqual(['lt']);
  });
});
