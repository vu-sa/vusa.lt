import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import SiteContentLists from '../SiteContentLists.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('SiteContentLists', () => {
  it('renders compact event and news previews with their public links', () => {
    const wrapper = mount(SiteContentLists, {
      props: {
        events: [{ id: 1, title: 'Renginys', date: '2026-09-25T10:00:00Z', public_url: 'https://www.vusa.test/lt/renginys' }] as App.Entities.Calendar[],
        news: [{ id: 2, title: 'Naujiena', lang: 'lt', permalink: 'naujiena', image: null, publish_time: '2026-09-23T10:00:00Z', public_url: 'https://www.vusa.test/lt/naujiena' }],
      },
    });

    expect(wrapper.find('[data-slot="event-card"] a').attributes('href')).toBe('https://www.vusa.test/lt/renginys');
    expect(wrapper.find('[data-slot="news-card"]').attributes('href')).toBe('https://www.vusa.test/lt/naujiena');
  });
});
