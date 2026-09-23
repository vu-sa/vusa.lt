import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import ShellAccountMenu from '../ShellAccountMenu.vue';

import { commonStubs } from '@/tests/stubs';
import { router } from '@/mocks/inertia.mock';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const stubs = {
  ...commonStubs,
  DropdownMenuSub: { template: '<div data-testid="dropdown-menu-sub"><slot /></div>' },
  DropdownMenuSubTrigger: { template: '<button type="button" data-testid="dropdown-sub-trigger"><slot /></button>' },
  DropdownMenuSubContent: { template: '<div data-testid="dropdown-sub-content"><slot /></div>' },
  AccessibilityMenu: { template: '<div data-testid="accessibility-menu" />' },
};

const mountMenu = () => mount(ShellAccountMenu, {
  global: { stubs },
});

describe('ShellAccountMenu', () => {
  it('renders user initials and name in the trigger button', () => {
    const wrapper = mountMenu();
    const trigger = wrapper.find('button');

    expect(trigger.text()).toContain('TU'); // Test User initials
    expect(trigger.text()).toContain('Test User');
  });

  it('renders user name and email in the identity header', () => {
    const wrapper = mountMenu();
    const content = wrapper.find('[data-testid="dropdown-menu-content"]');

    expect(content.text()).toContain('Test User');
    expect(content.text()).toContain('test@vusa.lt');
  });

  it('renders navigation links to profile, roles, and notifications', () => {
    const wrapper = mountMenu();
    const links = wrapper.findAll('a');

    expect(links.some(l => l.attributes('href')?.includes('/profile') || l.text().includes('shell.chrome.account'))).toBe(true);
    expect(links.some(l => l.text().includes('shell.account.roles'))).toBe(true);
    expect(links.some(l => l.text().includes('shell.account.notifications'))).toBe(true);
  });

  it('renders submenu triggers for appearance, help, and about', () => {
    const wrapper = mountMenu();
    const content = wrapper.find('[data-testid="dropdown-menu-content"]');

    expect(content.text()).toContain('shell.account.appearance');
    expect(content.text()).toContain('shell.account.help');
    expect(content.text()).toContain('shell.account.about');
  });

  it('renders action to trigger logout', async () => {
    const wrapper = mountMenu();
    const logoutItem = wrapper.findAll('button').find(b => b.text().includes('auth.logout'));

    expect(logoutItem?.exists()).toBe(true);
    await logoutItem?.trigger('click');
    expect(router.post).toHaveBeenCalled();
  });
});
