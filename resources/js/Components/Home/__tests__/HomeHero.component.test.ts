import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import HomeHero from '../HomeHero.vue';

import { communityPhotos } from '@/Constants/communityPhotos';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const news = {
  id: 7,
  title: 'Pirmakursių stovykla',
  image: '/uploads/news/stovykla.jpg',
  publish_time: '2026-09-21T10:00:00Z',
  public_url: 'https://www.vusa.test/lt/naujiena/stovykla',
  archive_url: 'https://www.vusa.test/lt/naujienos',
};

describe('HomeHero', () => {
  it('shows the news photo with its headline and links to the article and the archive', () => {
    const wrapper = mount(HomeHero, { props: { greeting: 'Labas, Justinai', news } });

    expect(wrapper.find('h1').text()).toBe('Labas, Justinai');
    expect(wrapper.find('img').attributes('src')).toBe(news.image);
    expect(wrapper.find('h2 a').attributes('href')).toBe(news.public_url);
    expect(wrapper.find('[data-testid="hero-read"]').attributes('href')).toBe(news.public_url);
    expect(wrapper.find('[data-testid="hero-archive"]').attributes('href')).toBe(news.archive_url);
  });

  it('keeps the headline as plain text when the article has no public address yet', () => {
    const wrapper = mount(HomeHero, { props: { greeting: 'Labas', news: { ...news, public_url: null } } });

    expect(wrapper.find('h2 a').exists()).toBe(false);
    expect(wrapper.find('[data-testid="hero-read"]').exists()).toBe(false);
    expect(wrapper.find('h2').text()).toBe(news.title);
  });

  it('falls back to a community photo and drops the news block when there is no news', () => {
    const wrapper = mount(HomeHero, { props: { greeting: 'Labas', news: null } });

    expect(communityPhotos.map(photo => photo.src)).toContain(wrapper.find('img').attributes('src'));
    expect(wrapper.find('[data-testid="hero-news"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="hero-date"]').text()).not.toBe('');
  });
});
