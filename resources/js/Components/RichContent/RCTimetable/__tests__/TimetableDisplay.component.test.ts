import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import TimetableDisplay from '../TimetableDisplay.vue';

import type { Timetable } from '@/Types/contentParts';

function makeElement(rows: Partial<Timetable['json_content'][number]>[], options: Record<string, unknown> = {}) {
  return {
    id: 1,
    type: 'timetable',
    json_content: rows,
    options,
    order: 1,
  } as unknown as models.ContentPart;
}

describe('RichContent/RCTimetable/TimetableDisplay.vue', () => {
  it('renders nothing when no row has a start time', () => {
    const wrapper = mount(TimetableDisplay, {
      props: { element: makeElement([{ title: 'Be laiko' }, { title: 'Kitas' }]) },
    });

    expect(wrapper.html()).toBe('<!--v-if-->');
  });

  it('shows both the start and end time, not only the start', () => {
    const wrapper = mount(TimetableDisplay, {
      props: { element: makeElement([{ title: 'Dėl veiklos plano', startTime: '18:30', endTime: '19:00' }]) },
    });

    expect(wrapper.text()).toContain('18:30–19:00');
    expect(wrapper.text()).toContain('Dėl veiklos plano');
  });

  it('shows only the start time when no end time was set', () => {
    const wrapper = mount(TimetableDisplay, {
      props: { element: makeElement([{ startTime: '18:30', title: 'Klausimas' }]) },
    });

    expect(wrapper.text()).toContain('18:30');
    expect(wrapper.text()).not.toContain('–');
  });

  it('trims seconds off a HH:MM:SS value', () => {
    const wrapper = mount(TimetableDisplay, {
      props: { element: makeElement([{ startTime: '18:30:00', title: 'Su sekundėmis' }]) },
    });

    expect(wrapper.text()).toContain('18:30');
    expect(wrapper.text()).not.toContain('18:30:00');
  });

  it('sorts rows chronologically, not by their authored order', () => {
    const wrapper = mount(TimetableDisplay, {
      props: { element: makeElement([
        { startTime: '19:00', title: 'Antras punktas' },
        { startTime: '18:00', title: 'Pirmas punktas' },
      ]) },
    });

    const rows = wrapper.findAll('.divide-y > div');
    expect(rows).toHaveLength(2);
    expect(rows[0]!.text()).toContain('Pirmas punktas');
    expect(rows[1]!.text()).toContain('Antras punktas');
  });

  it('excludes rows with no start time from the list', () => {
    const wrapper = mount(TimetableDisplay, {
      props: { element: makeElement([
        { startTime: '18:00', title: 'Nurodytas laikas' },
        { title: 'Be laiko' },
      ]) },
    });

    expect(wrapper.text()).toContain('Nurodytas laikas');
    expect(wrapper.text()).not.toContain('Be laiko');
  });

  it('uses the authored title when provided, falling back to the default heading', () => {
    const defaultHeading = mount(TimetableDisplay, {
      props: { element: makeElement([{ startTime: '10:00', title: 'Vienas' }]) },
    });

    expect(defaultHeading.text()).toContain('Tvarkaraštis');

    const customHeading = mount(TimetableDisplay, {
      props: { element: makeElement([{ startTime: '10:00', title: 'Vienas' }], { title: 'Programa' }) },
    });

    expect(customHeading.text()).toContain('Programa');
  });
});
