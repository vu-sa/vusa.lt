import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import MobileBottomBar from '../MobileBottomBar.vue';

import { atstovavimas, pradzia } from './fixtures';

import { createMockPage } from '@/tests/helpers/createMockPage';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const withUnread = (count: number) => vi.mocked(usePage).mockReturnValue(createMockPage({
  auth: { user: { unreadNotifications: Array.from({ length: count }, (_, index) => ({ id: String(index), read_at: null, data: {} })) } },
}) as ReturnType<typeof usePage>);

beforeEach(() => withUnread(0));

const mountBar = (props: Record<string, unknown> = {}) => mount(MobileBottomBar, {
  props: { activeWorkspace: pradzia, activeSection: pradzia.sections[0], canCreate: true, ...props },
});

const tabs = (wrapper: ReturnType<typeof mount>) => wrapper.findAll('a, button').map(node => node.text());

describe('MobileBottomBar', () => {
  it('shows Pradžia, Užduotys, create, Pranešimai and Meniu', () => {
    const wrapper = mountBar();

    expect(tabs(wrapper)).toEqual([
      'shell.workspaces.pradzia.title',
      'shell.sections.uzduotys',
      '', // the icon-only create button
      'shell.sections.pranesimai',
      'shell.chrome.menu',
    ]);
    expect(wrapper.find('[aria-label="shell.chrome.create"]').exists()).toBe(true);
  });

  it('hides the create button when the user has nothing to create', () => {
    expect(mountBar({ canCreate: false }).find('[aria-label="shell.chrome.create"]').exists()).toBe(false);
  });

  it('lights exactly the Pradžia tab the user is on', () => {
    const lit = (activeSection: unknown) => mountBar({ activeSection }).findAll('a')
      .map(link => link.classes().includes('border-brand-fill'));

    expect(lit(pradzia.sections[0])).toEqual([true, false, false]);
    expect(lit(pradzia.sections[1])).toEqual([false, true, false]);
    expect(lit(pradzia.sections[2])).toEqual([false, false, true]);
  });

  it('lights Meniu while the user is in another workspace', () => {
    const wrapper = mountBar({ activeWorkspace: atstovavimas, activeSection: atstovavimas.sections[1] });

    expect(wrapper.findAll('a').every(link => link.classes().includes('border-transparent'))).toBe(true);
    expect(wrapper.findAll('button').at(-1)?.classes()).toContain('border-brand-fill');
  });

  it('counts unread notifications on the Pranešimai tab', () => {
    expect(mountBar().find('[data-slot="notification-count"]').exists()).toBe(false);

    withUnread(12);
    expect(mountBar().find('[data-slot="notification-count"]').text()).toContain('9+');
  });

  it('prefetches each navigation destination with a short fresh and stale cache window', () => {
    const wrapper = mountBar();

    for (const link of wrapper.findAllComponents({ name: 'InertiaLink' })) {
      expect(link.props('prefetch')).toBe(true);
      expect(link.props('cacheFor')).toEqual(['15s', '1m']);
    }
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
