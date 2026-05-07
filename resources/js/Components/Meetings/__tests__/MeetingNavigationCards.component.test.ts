import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';

import MeetingNavigationCards from '@/Components/Meetings/MeetingNavigationCards.vue';

describe('MeetingNavigationCards.vue', () => {
  let wrapper: ReturnType<typeof mount>;

  const createWrapper = (props = {}) => {
    return mount(MeetingNavigationCards, {
      props: {
        previousMeeting: null,
        nextMeeting: null,
        ...props,
      },
      global: {
        stubs: {
          Link: {
            template: '<a :href="href"><slot /></a>',
            props: ['href'],
          },
        },
      },
    });
  };

  beforeEach(() => {
    // empty
  });

  afterEach(() => {
    wrapper?.unmount();
  });

  describe('rendering', () => {
    it('renders nothing when both meetings are null', () => {
      wrapper = createWrapper();
      expect(wrapper.find('a').exists()).toBe(false);
    });

    it('renders previous meeting card', () => {
      wrapper = createWrapper({
        previousMeeting: { id: 'meeting-1', start_time: '2026-05-01T10:00:00', type: 'in-person' },
      });

      expect(wrapper.find('a').exists()).toBe(true);
      expect(wrapper.text()).toContain('Ankstesnis');
    });

    it('renders next meeting card', () => {
      wrapper = createWrapper({
        nextMeeting: { id: 'meeting-2', start_time: '2026-05-10T14:00:00', type: 'email' },
      });

      expect(wrapper.find('a').exists()).toBe(true);
      expect(wrapper.text()).toContain('Kitas');
    });

    it('renders both cards when both meetings exist', () => {
      wrapper = createWrapper({
        previousMeeting: { id: 'meeting-1', start_time: '2026-05-01T10:00:00', type: 'in-person' },
        nextMeeting: { id: 'meeting-2', start_time: '2026-05-10T14:00:00', type: 'email' },
      });

      const links = wrapper.findAll('a');
      expect(links).toHaveLength(2);
      expect(wrapper.text()).toContain('Ankstesnis');
      expect(wrapper.text()).toContain('Kitas');
    });

    it('shows spacer when only next meeting exists', () => {
      wrapper = createWrapper({
        nextMeeting: { id: 'meeting-2', start_time: '2026-05-10T14:00:00' },
      });

      const links = wrapper.findAll('a');
      const spacers = wrapper.findAll('div').filter(d => d.classes().includes('flex-1') && !d.find('a').exists());
      expect(links).toHaveLength(1);
      expect(spacers.length).toBeGreaterThan(0);
    });

    it('shows spacer when only previous meeting exists', () => {
      wrapper = createWrapper({
        previousMeeting: { id: 'meeting-1', start_time: '2026-05-01T10:00:00' },
      });

      const links = wrapper.findAll('a');
      const spacers = wrapper.findAll('div').filter(d => d.classes().includes('flex-1') && !d.find('a').exists());
      expect(links).toHaveLength(1);
      expect(spacers.length).toBeGreaterThan(0);
    });
  });

  describe('links', () => {
    it('links to correct previous meeting route', () => {
      wrapper = createWrapper({
        previousMeeting: { id: 'meeting-prev', start_time: '2026-05-01T10:00:00' },
      });

      const link = wrapper.find('a');
      expect(link.attributes('href')).toContain('/mocked-route/meetings.show');
    });

    it('links to correct next meeting route', () => {
      wrapper = createWrapper({
        nextMeeting: { id: 'meeting-next', start_time: '2026-05-10T14:00:00' },
      });

      const link = wrapper.find('a');
      expect(link.attributes('href')).toContain('/mocked-route/meetings.show');
    });
  });
});
