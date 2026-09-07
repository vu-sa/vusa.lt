import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import EventDetailsCard from '@/Components/Calendar/EventDetailsCard.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

describe('Calendar/EventDetailsCard.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  it('keeps passed-event status and category out of the sidebar and hides calendar export', () => {
    const wrapper = mount(EventDetailsCard, {
      props: {
        event: {
          id: 1,
          title: 'Praėjęs renginys',
          date: '2020-01-01T18:00:00+00:00',
          is_all_day: false,
          is_remote: false,
          category: { name: 'Konferencija' },
        },
        googleLink: 'https://calendar.google.com/event?eid=abc',
      },
    });

    expect(wrapper.text()).not.toContain('Konferencija');
    expect(wrapper.text()).not.toContain('Renginys įvyko');
    expect(wrapper.text()).not.toContain('Į kalendorių');
  });
});
