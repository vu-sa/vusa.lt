import { describe, expect, it } from 'vitest';
import { mount } from '@vue/test-utils';
import { CalendarDate } from '@internationalized/date';

import { Calendar } from '@/Components/ui/calendar';
import { RangeCalendar } from '@/Components/ui/range-calendar';

// 2026-09-01 is a Tuesday, so a Sunday-first grid would open on 30 August.
const september = new CalendarDate(2026, 9, 1);

describe('calendars', () => {
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

