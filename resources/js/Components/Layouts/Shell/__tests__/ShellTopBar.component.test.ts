import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import ShellTopBar from '../ShellTopBar.vue';

import { pradzia } from './fixtures';

import { SHELL_FORM_BAR_ID } from '@/Composables/useShellFocus';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
// `virtual:pwa-register` only exists inside a Vite build.
vi.mock('@/Composables/usePWA', () => ({ usePWA: () => ({ isPWA: false, setAppBadge: vi.fn() }) }));

const stubs = {
  Link: { template: '<a data-testid="shell-logo-link"><slot /></a>' },
  WorkspacePicker: { template: '<div data-testid="workspace-picker" />' },
  PaletteField: { template: '<div data-testid="palette-field" />' },
  NotificationsIndicator: { template: '<div data-testid="notifications-indicator" />' },
  ShellAccountMenu: { template: '<div data-testid="account-menu" />' },
  Button: { template: '<button><slot /></button>' },
};

const mountTopBar = (props: Record<string, unknown> = {}) => mount(ShellTopBar, {
  props: {
    workspaces: [pradzia],
    focused: false,
    ...props,
  },
  global: { stubs },
});

describe('ShellTopBar', () => {
  beforeEach(() => {
    vi.stubGlobal('route', () => 'http://localhost/mano');
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('renders logo, navigation chrome, notifications, and user account menu when not focused', () => {
    const wrapper = mountTopBar({ focused: false });

    expect(wrapper.find('img[alt="VU SA"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="workspace-picker"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="palette-field"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="notifications-indicator"]').exists()).toBe(true);
    expect(wrapper.find('[data-testid="account-menu"]').exists()).toBe(true);
    expect(wrapper.find(`#${SHELL_FORM_BAR_ID}`).classes()).toContain('contents');
  });

  it('hides logo, notifications, user account menu, and other navigation chrome when focused', () => {
    const wrapper = mountTopBar({ focused: true });

    expect(wrapper.find('img[alt="VU SA"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="workspace-picker"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="palette-field"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="notifications-indicator"]').exists()).toBe(false);
    expect(wrapper.find('[data-testid="account-menu"]').exists()).toBe(false);

    const formBar = wrapper.find(`#${SHELL_FORM_BAR_ID}`);
    expect(formBar.exists()).toBe(true);
    expect(formBar.classes()).toContain('flex-1');
  });

  it('gives way to the context bar on phones, but stays for a form\'s editor bar', () => {
    expect(mountTopBar({ focused: false }).find('header').classes()).toContain('max-md:hidden');
    expect(mountTopBar({ focused: true }).find('header').classes()).not.toContain('max-md:hidden');
  });
});
