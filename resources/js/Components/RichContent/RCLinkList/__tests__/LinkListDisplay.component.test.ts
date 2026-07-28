import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

import LinkListDisplay from '../LinkListDisplay.vue';
import type { LinkListResolved } from '@/Types/contentParts';

const stubs = {
  SmartLink: { props: ['href'], template: '<a :href="href"><slot /></a>' },
};

function makeResolved(overrides: Partial<LinkListResolved> = {}): LinkListResolved {
  return {
    type: 'link-list',
    items: [
      { id: 1, title: 'Prasideda rinkimai', href: '/a', imageUrl: '/img-a.jpg', publishedAt: '2026-01-10T00:00:00+00:00' },
      { id: 2, title: 'Naujas bendradarbiavimas', href: '/b', imageUrl: '/img-b.jpg', publishedAt: '2026-01-05T00:00:00+00:00' },
    ],
    meta: { total: 2, truncated: false, droppedForLocale: 0 },
    ...overrides,
  };
}

describe('LinkListDisplay', () => {
  it('photo style renders one card per resolved item', () => {
    const wrapper = mount(LinkListDisplay, {
      props: {
        element: { type: 'link-list', json_content: { links: [] }, options: { style: 'photo' } },
        resolved: makeResolved(),
      },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Prasideda rinkimai');
    expect(wrapper.text()).toContain('Naujas bendradarbiavimas');
  });

  it('compact style renders a divided list with a published date', () => {
    const wrapper = mount(LinkListDisplay, {
      props: {
        element: { type: 'link-list', json_content: { links: [] }, options: { style: 'compact' } },
        resolved: makeResolved(),
      },
      global: { stubs },
    });
    expect(wrapper.findAll('li')).toHaveLength(2);
  });

  it('renders no cards or list items when meta.total is 0', () => {
    const wrapper = mount(LinkListDisplay, {
      props: {
        element: { type: 'link-list', json_content: { links: [] }, options: { style: 'photo' } },
        resolved: makeResolved({ items: [], meta: { total: 0, truncated: false, droppedForLocale: 0 } }),
      },
      global: { stubs },
    });
    expect(wrapper.find('article').exists()).toBe(false);
    expect(wrapper.findAll('li')).toHaveLength(0);
  });

  it('never renders a manual link title as HTML', () => {
    const wrapper = mount(LinkListDisplay, {
      props: {
        element: { type: 'link-list', json_content: { links: [] }, options: { style: 'compact' } },
        resolved: makeResolved({
          items: [{ id: null, title: '<img src=x onerror=alert(1)>', href: 'https://vusa.lt', imageUrl: null, publishedAt: null }],
        }),
      },
      global: { stubs },
    });
    expect(wrapper.find('img[src="x"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('<img src=x onerror=alert(1)>');
  });
});
