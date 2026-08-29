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
  it('shows the logged-in user avatar image with AppSidebar roundness', () => {
    const wrapper = mountMenu({
      user: { id: 1, name: 'Testas Testaitis', profile_photo_path: '/storage/profile.jpg' },
    });

    const img = wrapper.find('img');
    expect(img.exists()).toBe(true);
    expect(img.attributes('src')).toBe('/storage/profile.jpg');

    // jsdom cannot render the CSS pipeline, so assert the wiring: the avatar
    // container carries the rounded shape the AppSidebar avatar uses.
    const avatar = img.element.closest('[data-slot="avatar"]');
    expect(avatar?.className).toContain('rounded-lg');

    expect(wrapper.text()).toContain('Testas Testaitis');
  });

  it('falls back to initials when the user has no photo', () => {
    const wrapper = mountMenu({
      user: { id: 1, name: 'Testas Testaitis', profile_photo_path: null },
    });

    expect(wrapper.find('img').exists()).toBe(false);
    expect(wrapper.find('[data-slot="avatar-fallback"]').text()).toBe('TE');
  });

  it('shows the login link without an avatar when logged out', () => {
    const wrapper = mountMenu({ user: null });

    expect(wrapper.find('[data-slot="avatar"]').exists()).toBe(false);
    expect(wrapper.text()).toContain('Mano VU SA');
  });
});
