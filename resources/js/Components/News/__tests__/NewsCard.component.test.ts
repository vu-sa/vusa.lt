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
