import { mount } from '@vue/test-utils';
import { Link } from '@inertiajs/vue3';
import { describe, expect, it, vi } from 'vitest';

import SiteContentLists from '../SiteContentLists.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const event = (origin: string) => ({ id: 1, title: 'Renginys', date: '2026-09-25T10:00:00Z', end_date: null, is_all_day: false, image: null as string | null, public_url: `${origin}/lt/renginys` });
const news = (origin: string) => ({ id: 2, title: 'Naujiena', lang: 'lt', permalink: 'naujiena', image: null, publish_time: '2026-09-23T10:00:00Z', public_url: `${origin}/lt/naujiena` });

describe('SiteContentLists', () => {
  it.each([
    window.location.origin,
    'https://mif.vusa.test',
  ])('lists events, then news, in one section with native public links on %s', (origin) => {
    const wrapper = mount(SiteContentLists, { props: { events: [event(origin)], news: [news(origin)] } });

    expect(wrapper.findAll('[data-slot="site-content"]')).toHaveLength(1);
    const rows = wrapper.findAll('[data-slot="site-content-event"], [data-slot="site-content-news"]');
    expect(rows.map(row => row.attributes('data-slot'))).toEqual(['site-content-event', 'site-content-news']);
    expect(rows.map(row => row.attributes('href'))).toEqual([`${origin}/lt/renginys`, `${origin}/lt/naujiena`]);
    expect(rows.map(row => row.text())).toEqual([expect.stringContaining('Renginys'), expect.stringContaining('Naujiena')]);
    expect(wrapper.findComponent(Link).exists()).toBe(false);
  });

  it('shows a thumbnail when there is one and falls back to the icon tile when it fails to load', async () => {
    const wrapper = mount(SiteContentLists, {
      props: { events: [{ ...event(window.location.origin), image: '/thumb.jpg' }], news: [{ ...news(window.location.origin), image: null }] },
    });
    const thumbs = () => wrapper.findAll('[data-slot="site-content-thumb"]');

    expect(thumbs()[0]!.find('img').attributes('src')).toBe('/thumb.jpg');
    expect(thumbs()[1]!.find('img').exists()).toBe(false);

    await thumbs()[0]!.get('img').trigger('error');
    expect(thumbs()[0]!.find('img').exists()).toBe(false);
  });

  it('renders nothing when there are neither events nor news', () => {
    expect(mount(SiteContentLists, { props: { events: [], news: [] } }).find('[data-slot="site-content"]').exists()).toBe(false);
  });
});
