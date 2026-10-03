import { afterEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';
import { Icon } from '@iconify/vue';

import QuickLink from '../QuickLink.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { ssrRoundTrip } from '@/tests/helpers/ssrRoundTrip';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const iconData = { body: '<path d="M1 2h3v4H1z" fill="currentColor"/>', width: 24, height: 24 };
const quickLink = { id: 1, text: 'Events', link: '/en/events', icon: 'calendar-24-regular', is_important: true };

afterEach(() => vi.clearAllMocks());

describe('QuickLink', () => {
  it('renders selected SVG paths on the server and hydrates without replacing them', async () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      publicAssets: { logoSrc: '/logo.svg', icons: { 'calendar-24-regular': iconData } },
    }));

    const result = await ssrRoundTrip(QuickLink, { quickLink });
    try {
      expect(result.html).toContain(iconData.body);
      expect(result.container.querySelector('svg')?.getAttribute('viewBox')).toBe('0 0 24 24');
      expect(result.container.querySelector('path')?.getAttribute('d')).toBe('M1 2h3v4H1z');
      expect(result.hydrationWarnings).toEqual([]);
    }
    finally {
      result.unmount();
    }
  });

  it('keeps the Iconify fallback for icons absent from the catalogue', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({ publicAssets: { logoSrc: '/logo.svg', icons: {} } }));
    const wrapper = mount(QuickLink, { props: { quickLink }, global: { stubs: { Icon: true } } });

    expect(wrapper.findComponent(Icon).props()).toMatchObject({ icon: 'fluent:calendar-24-regular', ssr: false });
    wrapper.unmount();
  });

  it('renders a link without an icon when no icon is selected', () => {
    const wrapper = mount(QuickLink, { props: { quickLink: { ...quickLink, icon: null } } });
    expect(wrapper.text()).toBe('Events');
    expect(wrapper.find('svg').exists()).toBe(false);
    wrapper.unmount();
  });
});
