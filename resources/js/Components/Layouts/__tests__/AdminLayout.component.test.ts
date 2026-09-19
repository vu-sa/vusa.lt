import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
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
const keyboardShortcutsStub = defineComponent({
  name: 'KeyboardShortcutsDialog',
  props: { open: Boolean },
  template: '<div data-stub="KeyboardShortcutsDialog" :data-open="open" />',
});
const mountedLayouts: ReturnType<typeof mount>[] = [];

const mountLayout = (newShell: boolean) => {
  vi.mocked(usePage).mockReturnValue(createMockPage({
    auth: { user: { id: 1, name: 'Test', ui_preferences: { appearance: { new_shell: newShell } } } },
  }));

  const wrapper = mount(AdminLayout, {
    slots: { default: '<p data-testid="page">Page content</p>' },
    global: {
      stubs: {
        Head: true,
        AdminShell: stub('AdminShell'),
        LegacyAdminShell: stub('LegacyAdminShell'),
        ActionWindow: true,
        AdminCommandPalette: true,
        KeyboardShortcutsDialog: keyboardShortcutsStub,
        Toaster: true,
        InstallBanner: true,
        UpdateBanner: true,
      },
    },
  });

  mountedLayouts.push(wrapper);

  return wrapper;
};

afterEach(() => {
  mountedLayouts.splice(0).forEach(wrapper => wrapper.unmount());
});

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

  it('opens the shared keyboard shortcuts dialog with ?', async () => {
    const wrapper = mountLayout(true);

    window.dispatchEvent(new KeyboardEvent('keydown', { key: '?', cancelable: true }));
    await wrapper.vm.$nextTick();

    expect(wrapper.find('[data-stub="KeyboardShortcutsDialog"]').attributes('data-open')).toBe('true');
  });

  it('focuses the active collection search with /', () => {
    const wrapper = mountLayout(true);
    const search = document.createElement('input');
    const focus = vi.spyOn(search, 'focus');
    search.setAttribute('data-admin-collection-search', '');
    document.body.append(search);

    window.dispatchEvent(new KeyboardEvent('keydown', { key: '/', cancelable: true }));

    expect(focus).toHaveBeenCalledOnce();
    search.remove();
    wrapper.unmount();
  });
});
