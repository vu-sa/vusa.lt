import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import EventCard from '@/Components/Calendar/EventCard.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

function makeEvent(overrides: Record<string, unknown> = {}) {
  return {
    id: 1,
    title: 'Test renginys',
    date: '2099-01-01T18:00:00+00:00',
    end_date: null,
    is_all_day: false,
    location: null,
    is_remote: false,
    main_image_url: null,
    facebook_url: null,
    tenant: { shortname: 'VU SA' },
    category: { name: 'Konferencija' },
    ...overrides,
  };
}

describe('Calendar/EventCard.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  function mountCard(props: Record<string, unknown> = {}) {
    return mount(EventCard, {
      props: { event: makeEvent(), ...props },
    });
  }

  it('renders the address for an in-person event', () => {
    const wrapper = mountCard({ event: makeEvent({ location: 'Saulėtekio al. 9' }) });

    expect(wrapper.text()).toContain('Saulėtekio al. 9');
    expect(wrapper.text()).not.toContain('Nuotolinis renginys');
  });

  it('shows a remote badge instead of a location for a remote event, even if location is set', () => {
    const wrapper = mountCard({ event: makeEvent({ is_remote: true, location: 'Should not show' }) });

    expect(wrapper.text()).toContain('Nuotolinis renginys');
    expect(wrapper.text()).not.toContain('Should not show');
  });

  it('shows neither a location nor a remote badge when there is no location and it is not remote', () => {
    const wrapper = mountCard();

    expect(wrapper.text()).not.toContain('Nuotolinis renginys');
  });

  it('renders category and tenant badges by default', () => {
    const wrapper = mountCard();

    expect(wrapper.text()).toContain('Konferencija');
    expect(wrapper.text()).toContain('VU SA');
  });

  it('hides badges when showBadges is false', () => {
    const wrapper = mountCard({ showBadges: false });

    expect(wrapper.text()).not.toContain('Konferencija');
  });

  it('shows Peržiūrėti and hides social/calendar actions for past events', () => {
    const wrapper = mountCard({ variant: 'past', googleLink: 'https://calendar.google.com/x' });

    expect(wrapper.text()).toContain('Peržiūrėti');
    expect(wrapper.text()).not.toContain('Daugiau');
    expect(wrapper.findAll('a').some(a => a.attributes('href') === 'https://calendar.google.com/x')).toBe(false);
  });

  it('shows Daugiau and the Google Calendar action for upcoming events', () => {
    const wrapper = mountCard({ googleLink: 'https://calendar.google.com/x' });

    expect(wrapper.text()).toContain('Daugiau');
    expect(wrapper.findAll('a').some(a => a.attributes('href') === 'https://calendar.google.com/x')).toBe(true);
  });
});
