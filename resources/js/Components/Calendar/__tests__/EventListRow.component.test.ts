import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import EventListRow from '@/Components/Calendar/EventListRow.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

function makeEvent(overrides: Record<string, unknown> = {}) {
  return {
    id: 7,
    title: 'Studentų forumas',
    date: '2099-01-01T18:00:00+00:00',
    end_date: null,
    is_all_day: false,
    location: null,
    is_remote: false,
    ...overrides,
  };
}

describe('Calendar/EventListRow.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  const mountRow = (event: Record<string, unknown> = {}) =>
    mount(EventListRow, { props: { event: makeEvent(event) as never } });

  it('names the event and links to it', () => {
    const wrapper = mountRow();

    expect(wrapper.text()).toContain('Studentų forumas');
    expect(wrapper.find('a').attributes('href')).toContain('7');
  });

  it('says where an in-person event happens', () => {
    expect(mountRow({ location: 'Saulėtekio al. 9' }).text()).toContain('Saulėtekio al. 9');
  });

  it('calls a remote event remote instead of showing an address', () => {
    const wrapper = mountRow({ is_remote: true, location: 'https://teams.example' });

    expect(wrapper.text()).toContain('Nuotolinis renginys');
    expect(wrapper.text()).not.toContain('https://teams.example');
  });

  /**
   * The date badge shows only the first day, so a span that runs past it has to be
   * spelled out in the line beneath — otherwise a three-day event reads as one.
   */
  it('spells out a multi-day span the badge cannot show', () => {
    const single = mountRow().text();
    const span = mountRow({ end_date: '2099-01-03T18:00:00+00:00' }).text();

    expect(span.length).toBeGreaterThan(single.length);
  });
});
