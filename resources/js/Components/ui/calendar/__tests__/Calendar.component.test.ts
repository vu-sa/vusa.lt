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
});
