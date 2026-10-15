import { afterEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import MobileMenuPanel from '../MobileMenuPanel.vue';

import { atstovavimas, pradzia, rezervacijos } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const tour = vi.hoisted(() => ({ available: false, start: vi.fn() }));
vi.mock('@/Composables/useTourProvider', async () => {
  const { computed } = await import('vue');

  return { useTour: () => ({ hasTour: computed(() => tour.available), startTour: tour.start }) };
});

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
  tour.available = false;
  tour.start.mockClear();
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

  it('opens only the active workspace initially', () => {
    const wrapper = mountPanel();
    const workspaces = wrapper.findAll('[data-slot="mobile-menu-workspace"]');

    expect(workspaces).toHaveLength(3);
    expect(workspaces.map(workspace => workspace.find('button').attributes('aria-expanded'))).toEqual(['false', 'true', 'false']);
    expect(workspaces.map(workspace => workspace.find('ul').isVisible())).toEqual([false, true, false]);
    expect(workspaces[1].find('button').attributes('aria-controls')).toBe(workspaces[1].find('ul').attributes('id'));
  });

  it('toggles sections and keeps at most one workspace open', async () => {
    const wrapper = mountPanel();
    const workspaces = wrapper.findAll('[data-slot="mobile-menu-workspace"]');

    await workspaces[0].find('button').trigger('click');
    expect(wrapper.findAll('[data-slot="mobile-menu-workspace"]').map(workspace => workspace.find('button').attributes('aria-expanded'))).toEqual(['true', 'false', 'false']);
    expect(wrapper.findAll('[data-slot="mobile-menu-workspace"]').map(workspace => workspace.find('ul').isVisible())).toEqual([true, false, false]);

    await workspaces[0].find('button').trigger('click');
    expect(wrapper.findAll('[data-slot="mobile-menu-workspace"]').map(workspace => workspace.find('button').attributes('aria-expanded'))).toEqual(['false', 'false', 'false']);
  });

  it('reopens on the active workspace after closing', async () => {
    const wrapper = mountPanel();

    await wrapper.findAll('[data-slot="mobile-menu-workspace"]')[0].find('button').trigger('click');
    await wrapper.setProps({ open: false });
    await wrapper.setProps({ open: true });

    expect(wrapper.findAll('[data-slot="mobile-menu-workspace"]').map(workspace => workspace.find('button').attributes('aria-expanded'))).toEqual(['false', 'true', 'false']);
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

  it('offers the page tour only when the page has one', () => {
    expect(mountPanel().find('[data-slot="mobile-menu-tour"]').exists()).toBe(false);

    tour.available = true;
    expect(mountPanel().find('[data-slot="mobile-menu-tour"]').exists()).toBe(true);
  });

  it('closes itself before starting the tour', async () => {
    tour.available = true;
    const wrapper = mountPanel();

    await wrapper.get('[data-slot="mobile-menu-tour"]').trigger('click');
    expect(wrapper.emitted('update:open')?.at(-1)).toEqual([false]);

    await flushPromises();
    expect(tour.start).toHaveBeenCalledOnce();
  });
});
