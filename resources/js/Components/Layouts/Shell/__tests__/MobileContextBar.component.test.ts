import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';

import MobileContextBar from '../MobileContextBar.vue';

import { atstovavimas, pradzia } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const toggleSearch = vi.fn();
vi.mock('@/Composables/useCommandPalette', () => ({ useCommandPalette: () => ({ toggle: toggleSearch }) }));

const mountBar = (props: Record<string, unknown> = {}) => mount(MobileContextBar, {
  props: { activeWorkspace: atstovavimas, activeSection: atstovavimas.sections[1], ...props },
  attachTo: document.body,
  global: { stubs: { TaskCountBadge: true } },
});

const switcher = () => document.body.querySelector('[data-slot="mobile-section-switcher"]');

beforeEach(() => {
  vi.stubGlobal('route', (name: string) => `/mano/${name}`);
});

afterEach(() => {
  document.body.innerHTML = '';
  vi.unstubAllGlobals();
});

describe('MobileContextBar', () => {
  it('names the workspace and the section you are in', () => {
    const wrapper = mountBar();
    const button = wrapper.get('[data-tour="section-switcher"]');

    expect(button.text()).toContain('shell.workspaces.atstovavimas.title');
    expect(button.text()).toContain('shell.sections.posedziai');
  });

  it('opens search from the bar', async () => {
    await mountBar().get('[data-tour="command-palette-mobile"]').trigger('click');

    expect(toggleSearch).toHaveBeenCalledOnce();
  });

  it('names only the workspace on its overview', () => {
    const text = mountBar({ activeSection: atstovavimas.sections[0] }).get('[data-tour="section-switcher"]').text();

    expect(text).toContain('shell.workspaces.atstovavimas.title');
    expect(text).not.toContain('shell.sections.apzvalga');
  });

  it('opens a switcher with only this workspace\'s sections, the current one marked', async () => {
    const wrapper = mountBar();

    await wrapper.get('[data-tour="section-switcher"]').trigger('click');
    await flushPromises();

    const links = [...switcher()!.querySelectorAll('a')];
    expect(links.map(link => link.getAttribute('href'))).toEqual(['/mano/dashboard.atstovavimas', '/mano/meetings.index']);
    expect(links.map(link => link.getAttribute('aria-current'))).toEqual([null, 'page']);
  });

  it('hands off to Meniu for other workspaces', async () => {
    const wrapper = mountBar();

    await wrapper.get('[data-tour="section-switcher"]').trigger('click');
    await flushPromises();
    (switcher()!.querySelector('[data-slot="mobile-section-switcher-menu"]') as HTMLElement).click();
    await flushPromises();

    expect(wrapper.emitted('menu')).toHaveLength(1);
    expect(switcher()).toBeNull();
  });

  it('goes straight to Meniu on a page outside every workspace', async () => {
    const wrapper = mountBar({ activeWorkspace: undefined, activeSection: undefined });

    expect(wrapper.get('[data-tour="section-switcher"]').text()).toContain('shell.chrome.product');
    await wrapper.get('[data-tour="section-switcher"]').trigger('click');

    expect(wrapper.emitted('menu')).toHaveLength(1);
  });

  it('shows Pradžia\'s task badge beside Užduotys', async () => {
    const wrapper = mountBar({ activeWorkspace: pradzia, activeSection: pradzia.sections[0] });

    await wrapper.get('[data-tour="section-switcher"]').trigger('click');
    await flushPromises();

    expect(switcher()!.querySelectorAll('task-count-badge-stub')).toHaveLength(1);
  });
});
