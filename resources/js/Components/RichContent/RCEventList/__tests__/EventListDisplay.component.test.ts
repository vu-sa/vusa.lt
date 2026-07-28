import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import EventListDisplay from '../EventListDisplay.vue';
import type { EventListResolved } from '@/Types/contentParts';

const stubs = {
  SmartLink: { props: ['href'], template: '<a :href="href"><slot /></a>' },
};

function makeGroupedResolved(): EventListResolved {
  return {
    type: 'event-list',
    groups: [
      {
        key: '1',
        label: 'VU SA MIF',
        items: [
          { id: 1, title: 'MIF stovykla', date: '2026-08-25T09:00:00+00:00', endDate: null, location: 'Trakai', isAllDay: false, ctoUrl: null, imageUrl: '/mif.jpg', href: '/events/1' },
        ],
      },
      {
        key: '2',
        label: 'VU SA FF',
        items: [
          { id: 2, title: 'FF stovykla', date: '2026-08-20T09:00:00+00:00', endDate: null, location: 'Druskininkai', isAllDay: false, ctoUrl: null, imageUrl: '/ff.jpg', href: '/events/2' },
        ],
      },
    ],
    items: [],
    meta: { total: 2, truncated: false, style: 'cards' },
  };
}

function makeFlatResolved(): EventListResolved {
  return {
    type: 'event-list',
    groups: [],
    items: [
      { id: 1, title: 'Susirinkimas', date: '2026-02-01T18:00:00+00:00', endDate: null, location: 'MIF', isAllDay: false, ctoUrl: null, imageUrl: null, href: '/events/1' },
    ],
    meta: { total: 1, truncated: false, style: 'list' },
  };
}

describe('EventListDisplay', () => {
  it('grouped cards style renders one card per tenant group', () => {
    const wrapper = mount(EventListDisplay, {
      props: {
        element: { type: 'event-list', json_content: {}, options: { style: 'cards', groupBy: 'tenant' } },
        resolved: makeGroupedResolved(),
      },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('VU SA MIF');
    expect(wrapper.text()).toContain('VU SA FF');
    expect(wrapper.text()).toContain('Trakai');
    expect(wrapper.text()).toContain('Druskininkai');
  });

  it('list style renders a flat chronological list', () => {
    const wrapper = mount(EventListDisplay, {
      props: {
        element: { type: 'event-list', json_content: {}, options: { style: 'list' } },
        resolved: makeFlatResolved(),
      },
      global: { stubs },
    });
    expect(wrapper.findAll('li')).toHaveLength(1);
    expect(wrapper.text()).toContain('Susirinkimas');
  });

  it('renders the empty state when meta.total is 0', () => {
    const wrapper = mount(EventListDisplay, {
      props: {
        element: { type: 'event-list', json_content: {}, options: { style: 'cards' } },
        resolved: { type: 'event-list', groups: [], items: [], meta: { total: 0, truncated: false, style: 'cards' } },
      },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('rich-content.event_list_empty');
  });

  it('shows a custom emptyMessage over the default when set', () => {
    const wrapper = mount(EventListDisplay, {
      props: {
        element: { type: 'event-list', json_content: {}, options: { emptyMessage: 'Nieko čia nėra' } },
        resolved: { type: 'event-list', groups: [], items: [], meta: { total: 0, truncated: false, style: 'cards' } },
      },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Nieko čia nėra');
  });
});
