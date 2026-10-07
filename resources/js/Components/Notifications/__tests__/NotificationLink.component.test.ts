import { Link, usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

import NotificationLink from '../NotificationLink.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('NotificationLink', () => {
  afterEach(() => vi.unstubAllEnvs());

  it.each([
    '/mano',
    '/mano/meetings/1?tab=agenda#vote',
    `${window.location.origin}/mano/institutions/1`,
  ])('keeps admin destination %s in Inertia', (href) => {
    const wrapper = mount(NotificationLink, { props: { href } });

    expect(wrapper.findComponent(Link).exists()).toBe(true);
    expect(wrapper.get('a').attributes('href')).toBe(href);
  });

  it.each([
    '/atsakymas/1?answer=met&expires=123&signature=abc%2Bdef',
    `${window.location.origin}/atsakymas/1?signature=abc`,
    '/lt/naujiena/sveiki',
    '/mano-other',
    'https://mif.vusa.lt/lt/renginiai/1',
    'https://other.example/mano/meetings/1',
    '//other.example/mano',
  ])('uses browser navigation for %s without changing its URL', (href) => {
    const wrapper = mount(NotificationLink, { props: { href } });

    expect(wrapper.findComponent(Link).exists()).toBe(false);
    expect(wrapper.get('a').attributes('href')).toBe(href);
    expect(wrapper.get('a').attributes('target')).toBeUndefined();
    expect(wrapper.get('a').attributes('prefetch')).toBeUndefined();
  });

  it('leaves ordinary and modified native clicks to the browser and forwards the event', () => {
    const wrapper = mount(NotificationLink, { props: { href: '/atsakymas/1?signature=abc' } });
    const prevented: boolean[] = [];
    wrapper.element.addEventListener('click', (event) => {
      prevented.push(event.defaultPrevented);
      event.preventDefault();
    });

    for (const ctrlKey of [false, true]) {
      wrapper.element.dispatchEvent(new MouseEvent('click', { ctrlKey, bubbles: true, cancelable: true }));
    }

    expect(prevented).toEqual([false, false]);
    expect(wrapper.emitted('click')).toHaveLength(2);
  });

  it('renders missing destinations as non-interactive content', async () => {
    const wrapper = mount(NotificationLink, { slots: { default: 'Pranešimas' } });

    await wrapper.trigger('click');

    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.text()).toBe('Pranešimas');
    expect(wrapper.emitted('click')).toBeUndefined();
  });

  it('uses the request origin when rendering on the server', async () => {
    vi.stubEnv('SSR', true);
    vi.mocked(usePage).mockReturnValue({
      ...createMockPage({ app: { url: 'http://www.vusa.test' }, ziggy: { location: 'http://mif.vusa.test/mano' } }),
      url: '/mano',
    });

    const html = await renderToString(createSSRApp({
      render: () => h(NotificationLink, { href: 'http://mif.vusa.test/mano/meetings/1' }, () => 'Posėdis'),
    }));

    expect(html).toContain('data-testid="inertia-link"');
  });
});
