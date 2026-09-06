import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import HeaderWordmark from '../HeaderWordmark.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

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
});
