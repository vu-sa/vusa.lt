import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import TimetableDisplay from '../TimetableDisplay.vue';

import type { Timetable } from '@/Types/contentParts';

function makeElement(rows: Timetable['json_content'] = [], options: Timetable['options'] = null): Timetable {
  return {
    type: 'timetable',
    json_content: rows,
    options: { title: 'Renginio programa', ...options },
  };
}

describe('TimetableDisplay', () => {
  it('renders heading and sorted timetable rows', () => {
    const wrapper = mount(TimetableDisplay, {
      props: {
        element: makeElement([
          { startTime: '12:00', endTime: '13:00', title: 'Pietūs' },
          { startTime: '10:00', endTime: '11:30', title: 'Registracija' },
        ]),
      },
    });

    expect(wrapper.text()).toContain('Renginio programa');
    const text = wrapper.text();
    // 10:00 should come before 12:00 in sorted view mode
    expect(text.indexOf('Registracija')).toBeLessThan(text.indexOf('Pietūs'));
  });

  it('renders empty state placeholder when editable and 0 rows', () => {
    const wrapper = mount(TimetableDisplay, {
      props: {
        element: makeElement([]),
        editable: true,
        blockKey: 'tt-1',
      },
    });

    expect(wrapper.text()).toContain('rich-content.no_timetable_rows');
  });

  it('renders inline editable controls when editable is true', () => {
    const wrapper = mount(TimetableDisplay, {
      props: {
        element: makeElement([
          { startTime: '10:00', endTime: '11:00', title: 'Įvadas' },
        ]),
        editable: true,
        blockKey: 'tt-1',
      },
      global: {
        stubs: {
          RCInlineText: { props: ['modelValue'], template: '<span class="inline-text-mock">{{ modelValue }}</span>' },
        },
      },
    });

    expect(wrapper.find('input[type="time"]').exists()).toBe(true);
    expect(wrapper.findAll('.inline-text-mock').length).toBeGreaterThanOrEqual(1);
  });
});
