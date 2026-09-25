import { usePage } from '@inertiajs/vue3';
import { mount } from '@vue/test-utils';
import type { Component } from 'vue';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import CreateShortcuts from '../CreateShortcuts.vue';
import QuickAccess from '../QuickAccess.vue';

import type { CommandAction } from '@/Components/CommandPalette/useCommandActions';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { pradzia, rezervacijos } from '@/Components/Layouts/Shell/__tests__/fixtures';

const state = vi.hoisted(() => ({ actions: [] as CommandAction[] }));

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Components/CommandPalette/useCommandActions', () => ({
  useCommandActions: () => ({ actions: { value: state.actions } }),
}));
vi.mock('@/Composables/useApi', () => ({
  useApiMutation: vi.fn(() => ({ execute: vi.fn().mockResolvedValue(undefined) })),
}));

const createAction = (id: string, workspaceKey: string): CommandAction => ({
  id,
  label: id,
  keywords: [],
  icon: 'span' as Component,
  category: 'create',
  workspaceKey,
  action: vi.fn(),
});

beforeEach(() => {
  state.actions = [];
  vi.mocked(usePage).mockReturnValue(createMockPage({
    auth: { can: { accessAdministration: false } },
    adminNavigation: { workspaces: [pradzia] },
  }) as ReturnType<typeof usePage>);
});

describe('home actions', () => {
  it('shows one permitted create action per workspace before repeats, with one brand fill', async () => {
    state.actions = [
      createAction('Fiksuoti posėdį', 'atstovavimas'),
      createAction('Pranešti apie veiklą', 'atstovavimas'),
      createAction('Nauja rezervacija', 'rezervacijos'),
      createAction('Rašyti naujieną', 'svetaine'),
      createAction('Nauja užduotis', 'organizacija'),
    ];

    const wrapper = mount(CreateShortcuts);
    const buttons = wrapper.findAll('button');

    expect(wrapper.find('h2').text()).toBe('home.create_title');
    expect(buttons.map(button => button.text())).toEqual([
      'Fiksuoti posėdį', 'Nauja rezervacija', 'Rašyti naujieną', 'Nauja užduotis',
    ]);
    expect(buttons.filter(button => button.classes().includes('bg-brand-fill'))).toHaveLength(1);
    expect(buttons.every(button => button.classes().includes('normal-case'))).toBe(true);

    await buttons[1].trigger('click');
    expect(state.actions[2].action).toHaveBeenCalledOnce();
  });

  it('hides create actions when the catalog permits none', () => {
    expect(mount(CreateShortcuts).find('[data-slot="create-shortcuts"]').exists()).toBe(false);
  });

  it('shows quick access only for the permitted administration and reservations areas', () => {
    expect(mount(QuickAccess).find('[data-slot="home-quick-access"]').exists()).toBe(false);

    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: { accessAdministration: true } },
      adminNavigation: { workspaces: [pradzia, rezervacijos] },
    }) as ReturnType<typeof usePage>);

    const links = mount(QuickAccess).findAll('a');
    expect(links).toHaveLength(2);
    expect(links[0].attributes('href')).toBe('/mocked-route/administration');
    expect(links[1].attributes('href')).toBe('/mocked-route/dashboard.reservations');
    expect(links[1].text()).toContain('home.quick_access.manage_reservations_description');
  });

  it('shows each quick access link independently when only that area is available', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: { accessAdministration: true } },
      adminNavigation: { workspaces: [pradzia] },
    }) as ReturnType<typeof usePage>);
    expect(mount(QuickAccess).findAll('a').map(link => link.attributes('href')))
      .toEqual(['/mocked-route/administration']);

    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: { accessAdministration: false } },
      adminNavigation: { workspaces: [pradzia, rezervacijos] },
    }) as ReturnType<typeof usePage>);
    expect(mount(QuickAccess).findAll('a').map(link => link.attributes('href')))
      .toEqual(['/mocked-route/dashboard.reservations']);
  });

  it('adds a link per registration form the server allows, even without other areas', () => {
    const wrapper = mount(QuickAccess, {
      props: {
        registrationForms: [
          { key: 'member', href: '/mano/forms/member' },
          { key: 'student_rep', href: '/mano/forms/reps' },
        ],
      },
    });

    expect(wrapper.findAll('a').map(link => [link.attributes('href'), link.find('[data-tile-label]').text()])).toEqual([
      ['/mano/forms/member', 'home.quick_access.member_registrations'],
      ['/mano/forms/reps', 'home.quick_access.rep_registrations'],
    ]);
  });

  it('lays out four permitted destinations in one desktop row', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: { accessAdministration: true } },
      adminNavigation: { workspaces: [pradzia, rezervacijos] },
    }) as ReturnType<typeof usePage>);

    const wrapper = mount(QuickAccess, {
      props: {
        registrationForms: [
          { key: 'member', href: '/mano/forms/member' },
          { key: 'student_rep', href: '/mano/forms/reps' },
        ],
      },
    });

    expect(wrapper.find('[data-slot="navigation-tiles"]').classes()).toContain('lg:[&>*]:basis-1/4');
    expect(wrapper.findAll('[data-slot="navigation-tiles"] > li')).toHaveLength(4);
  });

  it('lays out two destinations per row in the narrow home column', () => {
    const wrapper = mount(QuickAccess, {
      props: {
        columns: 2,
        registrationForms: [
          { key: 'member', href: '/mano/forms/member' },
          { key: 'student_rep', href: '/mano/forms/reps' },
        ],
      },
    });

    expect(wrapper.find('[data-slot="navigation-tiles"]').classes()).toContain('lg:[&>*]:basis-1/2');
  });
});
