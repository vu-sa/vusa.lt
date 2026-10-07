import { mount } from '@vue/test-utils';
import { Link } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';

import SiteContentLists from '../SiteContentLists.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('SiteContentLists', () => {
  it.each([
    window.location.origin,
    'https://mif.vusa.test',
  ])('renders compact event and news previews with native public links on %s', (origin) => {
    const wrapper = mount(SiteContentLists, {
      props: {
        events: [{ id: 1, title: 'Renginys', date: '2026-09-25T10:00:00Z', public_url: `${origin}/lt/renginys` }] as App.Entities.Calendar[],
        news: [{ id: 2, title: 'Naujiena', lang: 'lt', permalink: 'naujiena', image: null, publish_time: '2026-09-23T10:00:00Z', public_url: `${origin}/lt/naujiena` }],
      },
    });

    expect(wrapper.find('[data-slot="event-card"] a').attributes('href')).toBe(`${origin}/lt/renginys`);
    expect(wrapper.find('[data-slot="news-card"]').attributes('href')).toBe(`${origin}/lt/naujiena`);
    expect(wrapper.findComponent(Link).exists()).toBe(false);
    expect(wrapper.find('[data-slot="news-card"]').attributes('prefetch')).toBeUndefined();
  });
});
