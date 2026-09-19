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

const button = (wrapper: ReturnType<typeof mount>, title: string) =>
  wrapper.findAll('button').find(candidate => candidate.text().includes(title));

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

  it('opens with the current workspace expanded and the rest collapsed', () => {
    const wrapper = mountPanel();

    expect(button(wrapper, 'shell.workspaces.atstovavimas.title')?.attributes('aria-expanded')).toBe('true');
    expect(button(wrapper, 'shell.workspaces.pradzia.title')?.attributes('aria-expanded')).toBe('false');
    expect(wrapper.text()).toContain('shell.sections.posedziai');
    expect(wrapper.text()).not.toContain('shell.sections.uzduotys');
  });

  it('expands one workspace at a time', async () => {
    const wrapper = mountPanel();

    await button(wrapper, 'shell.workspaces.pradzia.title')?.trigger('click');

    expect(wrapper.text()).toContain('shell.sections.uzduotys');
    expect(wrapper.text()).not.toContain('shell.sections.posedziai');
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

  it('offers Visi skyriai only when allowed', () => {
    expect(mountPanel().text()).not.toContain('shell.chrome.all_sections');
    expect(mountPanel({ showAllSections: true }).text()).toContain('shell.chrome.all_sections');
  });
});
