import { describe, it, expect, vi } from 'vitest';
import { mount, config } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import SecondMenu from '../SecondMenu.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

// The template reads `$page` (a static global mock from tests/setup.ts), while the
// script's `usePage()` needs the shared factory mock — set both from the same props.
function mountMenu(auth: { user: Record<string, unknown> | null }) {
  const page = createMockPage({ auth: auth as never });
  vi.mocked(usePage).mockReturnValue(page);

  const originalPage = config.global.mocks.$page;
  config.global.mocks.$page = page;

  try {
    return mount(SecondMenu);
  }
  finally {
    config.global.mocks.$page = originalPage;
  }
}

describe('SecondMenu.vue', () => {
  it('renders the authenticated account control as a public outline button', () => {
    const wrapper = mountMenu({
      user: { id: 1, name: 'Testas Testaitis', profile_photo_path: '/storage/profile.jpg' },
    });

    expect(wrapper.text()).toContain('Mano VU SA');

    const link = wrapper.find('a[title="Testas Testaitis"]');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toBe('/mocked-route/dashboard');
    expect(link.classes()).toEqual(expect.arrayContaining([
      'border',
      'border-border',
      'font-bold',
      'uppercase',
      'tracking-wide',
      'w-31',
      'text-foreground/70',
    ]));
    expect(wrapper.find('[data-slot="mano-vusa-button-icon"]').classes()).toContain('text-brand');
    expect(wrapper.find('[data-slot="avatar"]').exists()).toBe(false);
  });

  it('uses the same account control when the user has no photo', () => {
    const wrapper = mountMenu({
      user: { id: 1, name: 'Testas Testaitis', profile_photo_path: null },
    });

    expect(wrapper.find('[data-slot="mano-vusa-button-icon"]').classes()).toContain('text-brand');
    expect(wrapper.find('[data-slot="avatar"]').exists()).toBe(false);
  });

  it('shows the login button with an account icon when logged out', () => {
    const wrapper = mountMenu({ user: null });

    expect(wrapper.find('[data-slot="avatar"]').exists()).toBe(false);
    expect(wrapper.find('[data-slot="mano-vusa-button-icon"]').exists()).toBe(true);
    expect(wrapper.find('[data-slot="mano-vusa-button-icon"]').classes()).not.toContain('text-brand');
    expect(wrapper.text()).toContain('Mano VU SA');

    const link = wrapper.find('a[title="auth.login"]');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toBe('/mocked-route/login');
  });

  it('hides the "more" dropdown when no tenant links overflow', () => {
    const page = createMockPage({
      auth: { user: null } as never,
      tenant: {
        links: [
          { id: 1, text: 'Nuoroda 1', link: '/one', icon: null, is_important: false },
          { id: 2, text: 'Nuoroda 2', link: '/two', icon: null, is_important: false },
        ],
      } as never,
    });
    vi.mocked(usePage).mockReturnValue(page);
    const originalPage = config.global.mocks.$page;
    config.global.mocks.$page = page;

    let wrapper;
    try {
      wrapper = mount(SecondMenu);
    }
    finally {
      config.global.mocks.$page = originalPage;
    }

    // jsdom performs no layout, so `clientWidth` stays 0 and nothing is ever
    // measured as overflowing — the dropdown must stay hidden rather than
    // duplicating every already-visible link.
    expect(wrapper.find('[title="Daugiau nuorodų"]').exists()).toBe(false);
    expect(wrapper.find('nav').classes()).toContain('grid-cols-[1fr_auto]');
  });
});
