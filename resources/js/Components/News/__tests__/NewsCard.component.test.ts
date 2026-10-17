import { Link } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import NewsCard from '../NewsCard.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const news = {
  id: 1,
  title: 'Naujiena',
  lang: 'lt',
  permalink: 'naujiena',
  image: null,
  public_url: `${window.location.origin}/lt/naujiena`,
};

describe('NewsCard', () => {
  it('keeps same-origin public navigation and prefetching in Inertia by default', () => {
    const wrapper = mount(NewsCard, { props: { news } });

    expect(wrapper.findComponent(Link).exists()).toBe(true);
    expect(wrapper.getComponent(Link).props('prefetch')).toBe(true);
  });

  it('uses a native anchor without prefetching when Inertia is disabled', () => {
    const wrapper = mount(NewsCard, { props: { news, inertia: false } });

    expect(wrapper.findComponent(Link).exists()).toBe(false);
    expect(wrapper.get('a').attributes('href')).toBe(news.public_url);
    expect(wrapper.get('a').attributes('prefetch')).toBeUndefined();
  });
});

it('keeps responsive image candidates on featured and grid cards', () => {
  const image = { id: 1, url: '/master.webp', thumb: '/thumb.webp', srcset: '/thumb.webp 400w, /large.webp 1600w',
    width: 1600, height: 900, focal_point: '40% 30%', alt: null, author: null };
  for (const size of ['sm', 'featured'] as const) {
    const wrapper = mount(NewsCard, { props: { news: { ...news, image_media: image }, size } });
    const img = wrapper.get('img');
    expect(img.attributes('srcset')).toBe(image.srcset);
    expect(img.attributes('sizes')).toContain('calc(');
    expect(img.attributes('style')).toContain('object-position: 40% 30%');
  }
});
