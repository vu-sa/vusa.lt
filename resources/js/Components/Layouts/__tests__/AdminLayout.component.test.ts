import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { usePage } from '@inertiajs/vue3';

import AdminLayout from '../AdminLayout.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
// `virtual:pwa-register` only exists inside a Vite build.
vi.mock('@/Composables/usePWA', () => ({ usePWA: () => ({ isPWA: false, setAppBadge: vi.fn() }) }));

// The layout's visit tracker calls `route().current()`, which the shared Ziggy mock lacks.
beforeEach(() => {
  vi.stubGlobal('route', () => ({ current: () => 'dashboard', params: {} }));
});

const stub = (name: string) => defineComponent({ name, setup: (_, { slots }) => () => h('div', { 'data-stub': name }, slots.default?.()) });

const mountLayout = (newShell: boolean) => {
  vi.mocked(usePage).mockReturnValue(createMockPage({
    auth: { user: { id: 1, name: 'Test', ui_preferences: { appearance: { new_shell: newShell } } } },
  }));

  return mount(AdminLayout, {
    slots: { default: '<p data-testid="page">Page content</p>' },
    global: {
      stubs: {
        Head: true,
        AdminShell: stub('AdminShell'),
        LegacyAdminShell: stub('LegacyAdminShell'),
        ActionWindow: true,
        AdminCommandPalette: true,
        Toaster: true,
        InstallBanner: true,
        UpdateBanner: true,
      },
    },
  });
};

describe('AdminLayout shell switch', () => {
  it('renders the new shell when the opt-in flag is on', () => {
    const wrapper = mountLayout(true);

    expect(wrapper.find('[data-stub="AdminShell"]').exists()).toBe(true);
    expect(wrapper.find('[data-stub="LegacyAdminShell"]').exists()).toBe(false);
  });

  it('keeps the legacy sidebar shell when the flag is off', () => {
    const wrapper = mountLayout(false);

    expect(wrapper.find('[data-stub="LegacyAdminShell"]').exists()).toBe(true);
    expect(wrapper.find('[data-stub="AdminShell"]').exists()).toBe(false);
  });

  it.each([true, false])('puts the page inside whichever shell is active (newShell=%s)', (newShell) => {
    const wrapper = mountLayout(newShell);
    const shell = wrapper.find(newShell ? '[data-stub="AdminShell"]' : '[data-stub="LegacyAdminShell"]');

    expect(shell.find('[data-testid="page"]').exists()).toBe(true);
  });

  it('drops the legacy font class once the new shell owns the typeface', () => {
    expect(mountLayout(true).find('.bg-background').classes()).not.toContain('font-admin');
    expect(mountLayout(false).find('.bg-background').classes()).toContain('font-admin');
  });

  it('keeps the shared overlays outside both shells', () => {
    const wrapper = mountLayout(true);

    for (const overlay of ['action-window-stub', 'admin-command-palette-stub', 'toaster-stub']) {
      expect(wrapper.find(overlay).exists()).toBe(true);
    }
  });
});
