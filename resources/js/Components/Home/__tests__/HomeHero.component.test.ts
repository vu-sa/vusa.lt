import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import HomeHero from '../HomeHero.vue';

import { communityPhotos } from '@/Constants/communityPhotos';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const image = { url: '/uploads/institutions/mif.jpg', focalPoint: '40% 30%' };

describe('HomeHero', () => {
  it('shows the institution image at its focal point without news text or actions', () => {
    const wrapper = mount(HomeHero, { props: { greeting: 'Labas, Justinai', image } });

    expect(wrapper.find('h1').text()).toBe('Labas, Justinai');
    expect(wrapper.find('img').attributes('src')).toBe(image.url);
    expect(wrapper.find('img').attributes('style')).toContain('object-position: 40% 30%');
    expect(wrapper.find('article').exists()).toBe(false);
    expect(wrapper.find('a').exists()).toBe(false);
  });

  it('uses a centered focal point when the institution has not set one', () => {
    const wrapper = mount(HomeHero, { props: { greeting: 'Labas', image: { ...image, focalPoint: null } } });

    expect(wrapper.find('img').attributes('style')).toContain('object-position: 50% 30%');
  });

  it('falls back to a community photo when there is no institution image', () => {
    const wrapper = mount(HomeHero, { props: { greeting: 'Labas', image: null } });

    expect(communityPhotos.map(photo => photo.src)).toContain(wrapper.find('img').attributes('src'));
    expect(wrapper.find('[data-testid="hero-date"]').text()).not.toBe('');
  });
});
