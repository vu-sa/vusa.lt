import { afterEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import MobileMenuPanel from '../MobileMenuPanel.vue';

import { atstovavimas, pradzia, rezervacijos } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountPanel = (props: Record<string, unknown> = {}) => mount(MobileMenuPanel, {
  props: {
    open: true,
    workspaces: [pradzia, atstovavimas, rezervacijos],
    activeWorkspace: atstovavimas,
    activeSection: atstovavimas.sections[1],
    ...props,
  },
  attachTo: document.body,
  global: { stubs: { Teleport: true } },
});

afterEach(() => {
  document.body.innerHTML = '';
});

describe('MobileMenuPanel', () => {
  it('renders nothing while closed', () => {
    expect(mountPanel({ open: false }).find('[role="dialog"]').exists()).toBe(false);
  });

  it('is a modal dialog labelled Meniu', () => {
    const dialog = mountPanel().find('[role="dialog"]');

    expect(dialog.attributes('aria-modal')).toBe('true');
    expect(dialog.attributes('aria-label')).toBe('shell.chrome.menu');
  });

  it('lists every workspace with all its sections, nothing folded away', () => {
    const wrapper = mountPanel();

    expect(wrapper.findAll('[data-slot="mobile-menu-workspace"]')).toHaveLength(3);
    expect(wrapper.text()).toContain('shell.sections.uzduotys');
    expect(wrapper.text()).toContain('shell.sections.posedziai');
    expect(wrapper.text()).toContain('shell.sections.rezervacijos');
  });

  it('marks the current section', () => {
    const current = mountPanel().findAll('a').find(link => link.text() === 'shell.sections.posedziai');

    expect(current?.attributes('aria-current')).toBe('page');
  });

  it('closes from the close button', async () => {
    const wrapper = mountPanel();

    await wrapper.find('[aria-label="shell.chrome.close_menu"]').trigger('click');

    expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false]);
  });

  it('closes on Escape', async () => {
    const wrapper = mountPanel();

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    await flushPromises();

    expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false]);
  });

  it('ignores Escape while closed', async () => {
    const wrapper = mountPanel({ open: false });

    window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
    await flushPromises();

    expect(wrapper.emitted('update:open')).toBeUndefined();
  });

  it('renders account, appearance, and help controls', () => {
    const wrapper = mountPanel();

    expect(wrapper.text()).toContain('shell.chrome.account');
    expect(wrapper.text()).toContain('shell.account.appearance');
    expect(wrapper.text()).toContain('shell.account.help');
    expect(wrapper.text()).toContain('shell.account.docs');
    expect(wrapper.text()).toContain('shell.account.report_problem');
    expect(wrapper.text()).toContain('shell.account.my_requests');
    expect(wrapper.text()).toContain('shell.account.whats_new');
    expect(wrapper.text()).toContain('shell.account.start_fm');
    expect(wrapper.text()).toContain('auth.logout');
    expect(wrapper.text()).toContain('auth.logout_microsoft');
  });
});
