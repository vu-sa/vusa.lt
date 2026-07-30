import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import CalendarEvent from '@/Pages/Public/CalendarEvent.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

const stubs = {
  EventActions: {
    props: {
      variant: { type: String, default: 'hero' },
      registrationUrl: { type: String, default: null },
      facebookUrl: { type: String, default: null },
      shareTitle: { type: String, default: '' },
      isPast: { type: Boolean },
      isLive: { type: Boolean },
      onImage: { type: Boolean },
    },
    template: '<a class="event-actions-stub" :data-variant="variant" :data-registration-url="registrationUrl" :data-on-image="onImage"><slot /></a>',
  },
  EventDetailsCard: {
    template: '<div class="event-details-card-stub" />',
  },
  EventImageGallery: {
    props: { images: Array, eventTitle: String },
    template: '<div class="event-image-gallery-stub" />',
  },
  UpcomingEventsCompact: {
    props: { events: Array, locale: String, excludeEventId: Number, maxVisible: Number },
    template: '<div class="upcoming-events-stub" :data-max-visible="maxVisible" />',
  },
};

function makeEvent(overrides: Record<string, unknown> = {}) {
  return {
    id: 1,
    title: 'Test Event',
    description: '<p>Event description</p>',
    date: '2030-01-01T18:00:00+00:00',
    end_date: null,
    is_all_day: false,
    cto_url: 'https://forms.example.com/register',
    facebook_url: null,
    video_url: null,
    images: [],
    ...overrides,
  };
}

describe('Public/CalendarEvent.vue', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  function mountPage(props: Record<string, unknown> = {}) {
    return mount(CalendarEvent, {
      props: {
        event: makeEvent(),
        calendar: [],
        googleLink: 'https://calendar.google.com/event?eid=abc',
        eventLocation: null,
        ...props,
      },
      global: { stubs },
    });
  }

  it('renders the sidebar after the main content on mobile', () => {
    const wrapper = mountPage();

    const main = wrapper.find('main');
    const aside = wrapper.find('aside');

    expect(main.exists()).toBe(true);
    expect(aside.exists()).toBe(true);
    expect(aside.classes()).toContain('order-2');
    expect(aside.classes()).toContain('lg:order-none');
  });

  it('passes maxVisible=3 to the upcoming events compact list', () => {
    const wrapper = mountPage({
      calendar: [
        { id: 2, title: 'Future 1', date: '2030-01-02T18:00:00+00:00' },
        { id: 3, title: 'Future 2', date: '2030-01-03T18:00:00+00:00' },
        { id: 4, title: 'Future 3', date: '2030-01-04T18:00:00+00:00' },
        { id: 5, title: 'Future 4', date: '2030-01-05T18:00:00+00:00' },
      ],
    });

    const upcoming = wrapper.find('.upcoming-events-stub');
    expect(upcoming.exists()).toBe(true);
    expect(upcoming.attributes('data-max-visible')).toBe('3');
  });

  it('renders the sticky mobile action bar when the event has a registration URL and is upcoming', () => {
    const wrapper = mountPage();

    const stickyBar = wrapper.find('.fixed.bottom-0');
    expect(stickyBar.exists()).toBe(true);
    expect(stickyBar.find('.event-actions-stub').attributes('data-variant')).toBe('sticky');
  });

  it('does not render the sticky mobile action bar for past events', () => {
    const wrapper = mountPage({
      event: makeEvent({ date: '2020-01-01T18:00:00+00:00' }),
    });

    expect(wrapper.find('.fixed.bottom-0').exists()).toBe(false);
  });

  it('does not render the sticky mobile action bar when there is no registration URL', () => {
    const wrapper = mountPage({
      event: makeEvent({ cto_url: null }),
    });

    expect(wrapper.find('.fixed.bottom-0').exists()).toBe(false);
  });

  it('renders hero actions via the EventHero slot', () => {
    const wrapper = mountPage();

    const heroActions = wrapper.find('.event-hero-wrapper').find('.event-actions-stub');
    expect(heroActions.exists()).toBe(true);
    expect(heroActions.attributes('data-variant')).toBe('hero');
    expect(heroActions.attributes('data-registration-url')).toBe('https://forms.example.com/register');
    expect(heroActions.attributes('data-on-image')).toBe('true');
  });
});
