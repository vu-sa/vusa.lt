import { describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';

import MobileBottomBar from '../MobileBottomBar.vue';

import { atstovavimas, pradzia } from './fixtures';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const mountBar = (props: Record<string, unknown> = {}) => mount(MobileBottomBar, {
  props: { primary: atstovavimas, activeWorkspace: pradzia, activeSection: pradzia.sections[0], canCreate: true, ...props },
});

const tabs = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('a, button').map(node => node.text());

describe('MobileBottomBar', () => {
  it('shows Pradžia, the primary workspace, Užduotys and Meniu around the create button', () => {
    const wrapper = mountBar();

    expect(tabs(wrapper)).toEqual([
      'shell.workspaces.pradzia.title',
      'shell.workspaces.atstovavimas.title',
      '', // the icon-only create button
      'shell.sections.uzduotys',
      'shell.chrome.menu',
    ]);
    expect(wrapper.find('[aria-label="shell.chrome.create"]').exists()).toBe(true);
  });

  it('hides the create button when the user has nothing to create', () => {
    expect(mountBar({ canCreate: false }).find('[aria-label="shell.chrome.create"]').exists()).toBe(false);
  });

  it('omits the primary slot when the user only has Pradžia', () => {
    expect(tabs(mountBar({ primary: undefined }))).not.toContain('shell.workspaces.atstovavimas.title');
  });

  it('lights Pradžia on the overview and Užduotys on the task list, never both', () => {
    const onOverview = mountBar().findAll('a');
    expect(onOverview[0].classes()).toContain('border-brand-fill');
    expect(onOverview.at(-1)?.classes()).toContain('border-transparent');

    const onTasks = mountBar({ activeSection: pradzia.sections[1] }).findAll('a');
    expect(onTasks[0].classes()).toContain('border-transparent');
    expect(onTasks.at(-1)?.classes()).toContain('border-brand-fill');
  });

  it('lights the primary workspace while the user is anywhere inside it', () => {
    const wrapper = mountBar({ activeWorkspace: atstovavimas, activeSection: atstovavimas.sections[1] });

    expect(wrapper.findAll('a')[1].classes()).toContain('border-brand-fill');
  });

  it('emits create and menu', async () => {
    const wrapper = mountBar();

    await wrapper.find('[aria-label="shell.chrome.create"]').trigger('click');
    await wrapper.findAll('button').at(-1)?.trigger('click');

    expect(wrapper.emitted('create')).toHaveLength(1);
    expect(wrapper.emitted('menu')).toHaveLength(1);
  });

  it('reflects the open menu on the Meniu tab for assistive tech', () => {
    expect(mountBar({ menuOpen: true }).findAll('button').at(-1)?.attributes('aria-expanded')).toBe('true');
  });
});
