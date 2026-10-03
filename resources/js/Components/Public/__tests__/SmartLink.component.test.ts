import { afterEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';
import { createSSRApp, h } from 'vue';
import { renderToString } from 'vue/server-renderer';

import SmartLink from '../SmartLink.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

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
