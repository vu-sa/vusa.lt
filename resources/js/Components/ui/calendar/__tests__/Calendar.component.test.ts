import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { CalendarDate } from '@internationalized/date';
import { i18nVue, loadLanguageAsync, reset } from 'laravel-vue-i18n';
import { nextTick } from 'vue';

import { Calendar } from '@/Components/ui/calendar';
import { RangeCalendar } from '@/Components/ui/range-calendar';

import en from '../../../../../../lang/en.json';
import lt from '../../../../../../lang/lt.json';

vi.unmock('laravel-vue-i18n');
afterEach(reset);

// 2026-09-01 is a Tuesday, so a Sunday-first grid would open on 30 August.
const september = new CalendarDate(2026, 9, 1);

describe('calendars', () => {
  it('updates the month and year accessible names when the app language changes', async () => {
    const wrapper = mount(Calendar, {
      props: { placeholder: september, locale: 'lt' },
      global: { plugins: [[i18nVue, { lang: 'lt', resolve: (lang: string) => lang === 'en' ? en : lt }]] },
    });
    const selectorNames = () => wrapper.findAll('select').map(select => select.attributes('aria-label'));
    expect(selectorNames()).toEqual(['Mėnuo', 'Metai']);

    await loadLanguageAsync('en');
    await nextTick();

    expect(selectorNames()).toEqual(['Month', 'Year']);
  });

  it('start the week on Monday', () => {
    const wrapper = mount(Calendar, { props: { placeholder: september, locale: 'lt' } });

    expect(wrapper.find('[data-reka-calendar-cell-trigger]').attributes('data-value')).toBe('2026-08-31');
  });

  it('start the range calendar week on Monday too', () => {
    const wrapper = mount(RangeCalendar, { props: { placeholder: september, locale: 'lt' } });

    expect(wrapper.find('[data-reka-calendar-cell-trigger]').attributes('data-value')).toBe('2026-08-31');
  });

  it('lets the month select move a calendar opened on a given placeholder', async () => {
    const wrapper = mount(Calendar, { props: { placeholder: september, locale: 'lt' } });

    await wrapper.find('select[aria-label="Mėnuo"]').setValue('3');

    expect(wrapper.find('[data-reka-calendar-cell-trigger]').attributes('data-value')).toBe('2026-02-23');
  });

  it('keeps the year select on the shown year after paging past the year range', async () => {
    const wrapper = mount(Calendar, { props: { placeholder: new CalendarDate(2027, 12, 1), yearRange: [2020, 2027], locale: 'lt' } });

    await wrapper.find('[data-slot=calendar-next-button]').trigger('click');

    expect((wrapper.find('select[aria-label="Metai"]').element as HTMLSelectElement).value).toBe('2028');
  });
});
