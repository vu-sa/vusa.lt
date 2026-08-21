import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import CalendarEvent from '@/Pages/Public/CalendarEvent.vue';
import { createMockPage } from '@/tests/helpers/createMockPage';

const stubs = {
  EventActions: {
    props: {
      registrationUrl: { type: String, default: null },
      facebookUrl: { type: String, default: null },
      shareTitle: { type: String, default: '' },
      isPast: { type: Boolean },
      isLive: { type: Boolean },
      onImage: { type: Boolean },
    },
    template: '<a class="event-actions-stub" :data-registration-url="registrationUrl" :data-on-image="onImage"><slot /></a>',
  },
  EventDetailsCard: {
    template: '<div class="event-details-card-stub" />',
  },
  EventImageGallery: {
    props: { images: Array, eventTitle: String },
    template: '<div class="event-image-gallery-stub" />',
  },
  EventCard: {
    props: { event: Object },
    template: '<div class="event-card-stub" />',
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

  function makeMeeting(overrides: Record<string, unknown> = {}) {
    return {
      id: '01hxyz',
      start_time: '2030-01-01T18:00:00+00:00',
      requires_student_perspective: false,
      institution: { id: '01inst', name: 'VU SA Parlamentas', alias: 'parlamentas' },
      agenda_items: [
        { id: 'a1', title: 'Dėl veiklos plano', order: 1, type: 'informational', start_time: '18:30:00' },
      ],
      documents: [],
      is_publicly_visible: true,
      ...overrides,
    };
  }

  describe('meeting behind the event', () => {
    it('renders no agenda for an ordinary event', () => {
      const wrapper = mountPage();

      expect(wrapper.text()).not.toContain('Darbotvarkė');
      expect(wrapper.text()).not.toContain('Posėdžio puslapis');
    });

    it('renders the agenda and a link to the meeting page', () => {
      const wrapper = mountPage({ meeting: makeMeeting() });

      expect(wrapper.text()).toContain('Darbotvarkė');
      expect(wrapper.text()).toContain('Dėl veiklos plano');
      // The agenda row's right-aligned time, trimmed from the TIME column's HH:MM:SS.
      expect(wrapper.text()).toContain('18:30');
      expect(wrapper.text()).toContain('Posėdžio puslapis');
    });

    it('hides the meeting-page link when the meeting is not publicly visible per settings', () => {
      const wrapper = mountPage({ meeting: makeMeeting({ is_publicly_visible: false }) });

      // The agenda still renders inline — only the link through to the meeting page is gated.
      expect(wrapper.text()).toContain('Darbotvarkė');
      expect(wrapper.text()).not.toContain('Posėdžio puslapis');
    });

    it('shows the sibling announcement dates next to the previous/next links', () => {
      const wrapper = mountPage({
        meeting: makeMeeting(),
        previousMeetingEvent: { id: 2, title: 'Ankstesnis posėdis', date: '2029-12-01T18:00:00+00:00' },
        nextMeetingEvent: { id: 3, title: 'Kitas posėdis', date: '2030-02-01T18:00:00+00:00' },
      });

      expect(wrapper.text()).toContain('Ankstesnis posėdis');
      expect(wrapper.text()).toContain('Kitas posėdis');
      // formatStaticTime with { year, month: 'long', day, hour, minute } on these dates.
      expect(wrapper.text()).toContain('2029');
      expect(wrapper.text()).toContain('2030');
    });

    it('renders no sibling nav when there is no previous or next announcement', () => {
      const wrapper = mountPage({ meeting: makeMeeting() });

      expect(wrapper.find('nav').exists()).toBe(false);
    });

    it('renders a documents section only when documents are linked', () => {
      expect(mountPage({ meeting: makeMeeting() }).text()).not.toContain('Dokumentai');

      const withDocs = mountPage({
        meeting: makeMeeting({
          documents: [{
            id: 7,
            title: 'VU SA Parlamento protokolas',
            content_type: 'VU SA Parlamento protokolai',
            document_date: '2030-01-01',
            anonymous_url: 'https://sharepoint.example/doc.pdf',
            language: 'lt',
          }],
        }),
      });

      expect(withDocs.text()).toContain('Dokumentai');
      expect(withDocs.text()).toContain('VU SA Parlamento protokolas');
    });
  });

  it('renders the sidebar after the main content on mobile', () => {
    const wrapper = mountPage();

    const main = wrapper.find('main');
    const aside = wrapper.find('aside');

    expect(main.exists()).toBe(true);
    expect(aside.exists()).toBe(true);
    expect(aside.classes()).toContain('order-2');
    expect(aside.classes()).toContain('lg:order-none');
  });

  it('renders other events below the description, capped to 2 (an even number for the two-column grid), excluding the current one', () => {
    const wrapper = mountPage({
      calendar: [
        { id: 1, title: 'Test Event', date: '2030-01-01T18:00:00+00:00' }, // current event, excluded
        { id: 2, title: 'Future 1', date: '2030-01-02T18:00:00+00:00' },
        { id: 3, title: 'Future 2', date: '2030-01-03T18:00:00+00:00' },
        { id: 4, title: 'Future 3', date: '2030-01-04T18:00:00+00:00' },
      ],
    });

    expect(wrapper.text()).toContain('Kiti renginiai');
    expect(wrapper.findAll('.event-card-stub')).toHaveLength(2);
  });

  it('does not render a sticky mobile action bar', () => {
    const wrapper = mountPage();

    expect(wrapper.find('.fixed.bottom-0').exists()).toBe(false);
  });

  it('renders hero actions via the EventHero slot', () => {
    const wrapper = mountPage();

    const hero = wrapper.find('[data-slot="event-hero"]');
    const heroActions = hero.find('.event-actions-stub');
    expect(heroActions.exists()).toBe(true);
    expect(heroActions.attributes('data-registration-url')).toBe('https://forms.example.com/register');
    expect(heroActions.attributes('data-on-image')).toBe('true');
  });

  it('renders hero actions off-image for the split style', () => {
    const wrapper = mountPage({
      event: makeEvent({ hero_style: 'split' }),
    });

    const heroActions = wrapper.find('[data-slot="event-hero"]').find('.event-actions-stub');
    expect(heroActions.exists()).toBe(true);
    expect(heroActions.attributes('data-on-image')).toBe('false');
  });

  it('renders hero actions off-image for the minimal style', () => {
    const wrapper = mountPage({
      event: makeEvent({ hero_style: 'minimal' }),
    });

    const heroActions = wrapper.find('[data-slot="event-hero"]').find('.event-actions-stub');
    expect(heroActions.exists()).toBe(true);
    expect(heroActions.attributes('data-on-image')).toBe('false');
  });

  it('passes upcoming state to hero actions for events with a registration URL', () => {
    const wrapper = mountPage();

    const heroActions = wrapper.find('[data-slot="event-hero"]').find('.event-actions-stub');
    expect(heroActions.attributes('data-registration-url')).toBe('https://forms.example.com/register');
  });

  it('passes past state to hero actions for past events', () => {
    const wrapper = mountPage({
      event: makeEvent({ date: '2020-01-01T18:00:00+00:00' }),
    });

    const heroActions = wrapper.find('[data-slot="event-hero"]').find('.event-actions-stub');
    expect(heroActions.exists()).toBe(true);
  });

  it('renders hero actions even when there is no registration URL', () => {
    const wrapper = mountPage({
      event: makeEvent({ cto_url: null }),
    });

    const heroActions = wrapper.find('[data-slot="event-hero"]').find('.event-actions-stub');
    expect(heroActions.exists()).toBe(true);
    expect(heroActions.attributes('data-registration-url')).toBeUndefined();
  });
});
