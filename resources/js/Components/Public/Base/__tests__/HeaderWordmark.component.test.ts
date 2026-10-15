import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import HeaderWordmark from '../HeaderWordmark.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { ssrRoundTrip } from '@/tests/helpers/ssrRoundTrip';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

describe('HeaderWordmark.vue', () => {
  it('renders the active tenant logo in the current language', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: { locale: 'en' },
      tenant: { alias: 'mif' },
    }));

    const wrapper = mount(HeaderWordmark);

    expect(wrapper.find('img').attributes('src')).toBe('/logos/hor/en/vusamif.lin.hor.tams.svg');
  });

  it('uses the preloaded server URL with high priority during SSR and hydration', async () => {
    const logoSrc = '/logos/hor/en/vusasa.lin.hor.tams.en.svg';
    vi.mocked(usePage).mockReturnValue(createMockPage({ publicAssets: { logoSrc } }));
    const result = await ssrRoundTrip(HeaderWordmark);
    try {
      expect(result.html).toContain(`src="${logoSrc}"`);
      expect(result.container.querySelector('img')?.getAttribute('fetchpriority')).toBe('high');
      expect(result.hydrationWarnings).toEqual([]);
    }
    finally {
      result.unmount();
    }
  });

  it('allows an explicit image override', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ publicAssets: { logoSrc: '/preloaded.svg' } }));
    const wrapper = mount(HeaderWordmark, { props: { src: '/override.svg' } });
    expect(wrapper.find('img').attributes('src')).toBe('/override.svg');
    wrapper.unmount();
  });
});
