import { afterEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

import { createMockPage } from '@/tests/helpers/createMockPage';

import SmartLink from '../SmartLink.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const serverRender = (href: string) => renderToString(createSSRApp({
  render: () => h(SmartLink, { href }, () => 'Naujiena'),
}));

describe('SmartLink server render', () => {
  afterEach(() => {
    vi.unstubAllEnvs();
  });

  it('keeps same-tenant absolute links in the Inertia router on a subdomain', async () => {
    vi.stubEnv('SSR', true);
    vi.mocked(usePage).mockReturnValue({
      ...createMockPage({ app: { url: 'http://www.vusa.test' }, ziggy: { location: 'http://mif.vusa.test/lt' } }),
      url: '/lt',
    });

    const html = await serverRender('http://mif.vusa.test/lt/naujiena/sveiki');

    expect(html).toContain('data-testid="inertia-link"');
    expect(html).not.toContain('target=');
  });

  it('opens other hosts in a new tab without Inertia-only attributes', async () => {
    vi.stubEnv('SSR', true);
    vi.mocked(usePage).mockReturnValue({
      ...createMockPage({ ziggy: { location: 'http://mif.vusa.test/lt' } }),
      url: '/lt',
    });

    const html = await serverRender('https://www.vu.lt');

    expect(html).toContain('target="_blank"');
    expect(html).not.toContain('prefetch');
  });
});

describe('SmartLink stored URLs', () => {
  it.each(['javascript:void(0)', 'data:text/plain,hello', 'vbscript:MsgBox(1)', 'java\tscript:void(0)'])('renders unsupported URL %s as text', async (href) => {
    const html = await serverRender(href);

    expect(html).toContain('Naujiena');
    expect(html).not.toContain('<a');
    expect(html).not.toContain('href=');
  });

  it.each(['mailto:info@example.com', 'tel:+37012345678'])('uses a native anchor for %s', async (href) => {
    const html = await serverRender(href);

    expect(html).toContain(`href="${href}"`);
    expect(html).not.toContain('data-testid="inertia-link"');
  });

  it.each(['/lt/kontaktai', '#anchor', 'kontaktai'])('preserves relative URL %s', async (href) => {
    const html = await serverRender(href);

    expect(html).toContain(`href="${href}"`);
    expect(html).toContain('data-testid="inertia-link"');
  });

  it('renders malformed absolute URLs without throwing', async () => {
    const html = await serverRender('https://[');

    expect(html).toContain('Naujiena');
  });
});

it('removes the anchor when its destination changes to an unsupported URL', async () => {
  const wrapper = mount(SmartLink, { props: { href: '/lt/kontaktai' }, slots: { default: 'Kontaktai' } });
  expect(wrapper.get('a').attributes('href')).toBe('/lt/kontaktai');

  await wrapper.setProps({ href: 'javascript:void(0)' });

  expect(wrapper.find('a').exists()).toBe(false);
  expect(wrapper.text()).toBe('Kontaktai');
});
