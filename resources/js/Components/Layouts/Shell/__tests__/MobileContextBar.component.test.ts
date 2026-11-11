import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { clearTrail, enterLearningStep } from '@/Composables/useRecordTrail';
import { createMockPage } from '@/tests/helpers/createMockPage';

import MobileContextBar from '../MobileContextBar.vue';

import { atstovavimas, kelias, learning, pradzia } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const toggleSearch = vi.fn();
vi.mock('@/Composables/useCommandPalette', () => ({ useCommandPalette: () => ({ toggle: toggleSearch }) }));

const mounted: Array<ReturnType<typeof mount>> = [];
const mountBar = (props: Record<string, unknown> = {}) => {
  const wrapper = mount(MobileContextBar, {
    props: { activeWorkspace: atstovavimas, activeSection: atstovavimas.sections[1], ...props },
    attachTo: document.body,
    global: { stubs: { TaskCountBadge: true } },
  });
  mounted.push(wrapper);
  return wrapper;
};

const switcher = () => document.body.querySelector('[data-slot="mobile-section-switcher"]');

beforeEach(() => {
  clearTrail();
  vi.mocked(usePage).mockReturnValue(createMockPage());
  vi.stubGlobal('route', (name?: string) => name ? `/mano/${name}` : { current: (pattern: string) => pattern === 'learningPath.reps' });
});

afterEach(() => {
  // Unmount before the route stub goes, or a pending recompute calls an undefined route().
  mounted.splice(0).forEach(wrapper => wrapper.unmount());
  clearTrail();
  document.body.innerHTML = '';
  vi.unstubAllGlobals();
});

describe('MobileContextBar', () => {
  it('names the active child and makes all children reachable in the switcher', async () => {
    const wrapper = mountBar({ activeWorkspace: learning, activeSection: kelias });
    const button = wrapper.get('[data-tour="section-switcher"]');

    expect(button.text()).toContain('shell.section_children.kelias.reps');
    await button.trigger('click');
    await flushPromises();

    const links = [...switcher()!.querySelectorAll('[data-slot="mobile-section-child"]')];
    expect(links.map(link => link.textContent?.trim())).toEqual([
      'shell.section_children.kelias.path', 'shell.section_children.kelias.reps', 'shell.section_children.kelias.library',
    ]);
    expect(links[1]!.getAttribute('href')).toBe('/mano/learningPath.reps');
    expect(links.map(link => link.getAttribute('aria-current'))).toEqual([null, 'page', null]);
    (links[1] as HTMLElement).click();
    await flushPromises();

    expect(switcher()).toBeNull();
  });

  it('names a learning record before its active child', () => {
    vi.mocked(usePage).mockReturnValue({ ...createMockPage(), component: 'Admin/Learning/ShowLearningStep' });
    enterLearningStep({ id: 42, title: 'Pasiruošk posėdžiui' });

    const text = mountBar({ activeWorkspace: learning, activeSection: kelias }).get('[data-tour="section-switcher"]').text();

    expect(text).toContain('Pasiruošk posėdžiui');
    expect(text).not.toContain('shell.section_children.kelias.reps');
  });

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
