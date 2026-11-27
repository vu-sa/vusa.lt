import { usePage } from '@inertiajs/vue3';
import { Bell, Bookmark, CalendarOff, CalendarPlus, LifeBuoy, Plus } from '@lucide/vue';
import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import type { AdminWorkspace } from '@/Composables/useAdminNavigation';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { atstovavimas, pradzia, rezervacijos, workspace } from '@/Components/Layouts/Shell/__tests__/fixtures';
import { ModelEnum } from '@/Types/enums';

import QuickAccess from '../QuickAccess.vue';
import CreateShortcuts from '../CreateShortcuts.vue';

const openActionWindow = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('@/Composables/useActionWindow', () => ({
  useActionWindow: () => ({ open: openActionWindow }),
}));
vi.mock('@/Composables/useApi', () => ({
  useApiMutation: vi.fn(() => ({ execute: vi.fn().mockResolvedValue(undefined) })),
}));

type CreateAction = AdminWorkspace['createActions'][number];

const routeAction = (key: string, routeName: string, entityType: ModelEnum | null = null): CreateAction => ({
  key,
  label: `shell.actions.${key}.title`,
  description: null,
  entityType,
  target: { kind: 'route', routeName },
});

const screenAction = (key: string, screen: string, entityType: ModelEnum | null = ModelEnum.MEETING): CreateAction => ({
  key,
  label: `shell.actions.${key}.title`,
  description: null,
  entityType,
  target: { kind: 'screen', screen },
});

const withWorkspaces = (workspaces: AdminWorkspace[]) => {
  vi.mocked(usePage).mockReturnValue(createMockPage({
    auth: { can: {} },
    adminNavigation: { workspaces },
  }) as ReturnType<typeof usePage>);
};

beforeEach(() => {
  openActionWindow.mockClear();
  withWorkspaces([pradzia]);
});

describe('home actions', () => {
  it('merges the meeting answers into one split row, keeps proper entity icons instead of Plus, and shows support request as a fourth row', async () => {
    withWorkspaces([
      workspace('pradzia', pradzia.sections, [
        routeAction('new_support_request', 'mySupportRequests.create', null),
      ]),
      workspace('atstovavimas', atstovavimas.sections, [
        screenAction('new_meeting', 'meeting.institution', ModelEnum.MEETING),
        screenAction('complete_meeting', 'meeting.pick', ModelEnum.MEETING),
        routeAction('new_problem', 'problems.create', ModelEnum.PROBLEM),
        screenAction('no_meeting', 'checkin.institution', ModelEnum.MEETING),
      ]),
      workspace('rezervacijos', rezervacijos.sections, [
        routeAction('new_reservation', 'reservations.create', ModelEnum.RESERVATION),
      ]),
      workspace('organizacija', [], [
        routeAction('duty_update', 'duties.updateUsersWizard', ModelEnum.DUTY),
      ]),
    ]);

    const wrapper = mount(CreateShortcuts);
    const listItems = wrapper.findAll('[data-slot="collection-row-list"] > li');
    const shortcuts = wrapper.findAll('[data-shortcut]');

    expect(wrapper.find('h2').text()).toBe('home.create_title');
    expect(listItems).toHaveLength(4);
    expect(listItems[0]!.find('[data-shortcut-group="meeting-activity"]').exists()).toBe(true);
    expect(listItems[0]!.findAll('button').map(button => [button.attributes('data-shortcut'), button.text(), button.attributes('data-leading')])).toEqual([
      ['create-new_meeting', 'home.shortcuts.new_meeting', 'true'],
      ['create-no_meeting', 'home.shortcuts.no_meeting', undefined],
    ]);
    expect(shortcuts.map(item => item.text())).toEqual([
      'home.shortcuts.new_meeting',
      'home.shortcuts.no_meeting',
      'home.shortcuts.new_problem',
      'home.shortcuts.new_reservation',
      'home.shortcuts.new_support_request',
    ]);
    expect(wrapper.find('[data-shortcut="create-new_meeting"]').findComponent(CalendarPlus).exists()).toBe(true);
    expect(wrapper.find('[data-shortcut="create-no_meeting"]').findComponent(CalendarOff).exists()).toBe(true);
    expect(wrapper.find('[data-shortcut="create-new_problem"]').findComponent(Bell).exists()).toBe(true);
    expect(wrapper.find('[data-shortcut="create-new_reservation"]').findComponent(Bookmark).exists()).toBe(true);
    expect(wrapper.find('[data-shortcut="create-new_support_request"]').findComponent(LifeBuoy).exists()).toBe(true);
    expect(wrapper.find('[data-slot="collection-row-list"]').findComponent(Plus).exists()).toBe(false);
    expect(shortcuts.some(item => item.classes().includes('bg-brand-fill'))).toBe(false);

    await wrapper.get('[data-shortcut="create-new_meeting"]').trigger('click');
    await wrapper.get('[data-shortcut="create-no_meeting"]').trigger('click');
    expect(openActionWindow).toHaveBeenNthCalledWith(1, { flow: 'meeting.create' });
    expect(openActionWindow).toHaveBeenNthCalledWith(2, { flow: 'check-in' });
    expect(wrapper.get('[data-shortcut="create-new_support_request"]').attributes('href')).toBe('/mocked-route/mySupportRequests.create');
  });

  it('keeps a single meeting row when only one meeting action is permitted', () => {
    withWorkspaces([
      workspace('atstovavimas', atstovavimas.sections, [
        screenAction('new_meeting', 'meeting.institution', ModelEnum.MEETING),
        routeAction('new_problem', 'problems.create', ModelEnum.PROBLEM),
      ]),
    ]);

    const wrapper = mount(CreateShortcuts);

    expect(wrapper.find('[data-shortcut-group="meeting-activity"]').exists()).toBe(false);
    expect(wrapper.findAll('[data-shortcut]').map(row => row.attributes('data-shortcut'))).toEqual([
      'create-new_meeting', 'create-new_problem',
    ]);
  });

  it('puts an unfinished reservation first, in place of a new one, and links it to the reservation form', () => {
    withWorkspaces([
      workspace('pradzia', pradzia.sections, [
        routeAction('new_support_request', 'mySupportRequests.create', null),
      ]),
      workspace('atstovavimas', atstovavimas.sections, [
        screenAction('new_meeting', 'meeting.institution', ModelEnum.MEETING),
        screenAction('no_meeting', 'checkin.institution', ModelEnum.MEETING),
        routeAction('new_problem', 'problems.create', ModelEnum.PROBLEM),
      ]),
      workspace('rezervacijos', rezervacijos.sections, [
        routeAction('new_reservation', 'reservations.create', ModelEnum.RESERVATION),
      ]),
    ]);

    const wrapper = mount(CreateShortcuts, {
      props: { reservationDraft: { name: 'Mokymai', count: 2, start_time: null, end_time: null } },
    });
    const listItems = wrapper.findAll('[data-slot="collection-row-list"] > li');
    const rows = wrapper.findAll('[data-shortcut]');

    expect(listItems).toHaveLength(4);
    expect(rows.map(row => row.attributes('data-shortcut'))).toEqual([
      'finish-reservation', 'create-new_meeting', 'create-no_meeting', 'create-new_problem', 'create-new_support_request',
    ]);
    expect(rows[0]!.text()).toContain('reservations.cart.finish');
    expect(rows[0]!.text()).toContain('Mokymai');
    expect(rows[0]!.attributes('data-leading')).toBe('true');
    expect(rows[0]!.attributes('href')).toBe('/mocked-route/reservations.create');
  });

  it('fills the shortcuts with one action per workspace when the user records no meetings', () => {
    withWorkspaces([
      workspace('rezervacijos', rezervacijos.sections, [
        routeAction('new_reservation', 'reservations.create', ModelEnum.RESERVATION),
      ]),
      workspace('organizacija', [], [
        routeAction('duty_update', 'duties.updateUsersWizard', ModelEnum.DUTY),
        routeAction('duty_periods', 'dutiables.timeline', ModelEnum.DUTIABLE),
      ]),
      workspace('atstovavimas', atstovavimas.sections, [
        routeAction('new_problem', 'problems.create', ModelEnum.PROBLEM),
      ]),
    ]);

    expect(mount(CreateShortcuts).findAll('[data-shortcut]').map(item => item.text())).toEqual([
      'home.shortcuts.new_problem', 'home.shortcuts.new_reservation', 'shell.actions.duty_update.title', 'shell.actions.duty_periods.title',
    ]);
  });

  it('hides create actions when the catalog permits none', () => {
    expect(mount(CreateShortcuts).find('[data-slot="create-shortcuts"]').exists()).toBe(false);
  });

  it('shows ViSAK first, then reservations, when both workspaces are accessible', () => {
    expect(mount(QuickAccess).find('[data-slot="home-quick-access"]').exists()).toBe(false);

    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: {} },
      adminNavigation: { workspaces: [pradzia, atstovavimas, rezervacijos] },
    }) as ReturnType<typeof usePage>);

    const links = mount(QuickAccess).findAll('a');
    expect(links).toHaveLength(2);
    expect(links[0]!.attributes('href')).toBe('/mocked-route/dashboard.atstovavimas');
    expect(links[0]!.text()).toContain('shell.workspaces.atstovavimas.title');
    expect(links[1]!.attributes('href')).toBe('/mocked-route/dashboard.reservations');
    expect(links[1]!.text()).toContain('home.quick_access.manage_reservations_description');
  });

  it('shows each quick access link independently when only that area is available', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: {} },
      adminNavigation: { workspaces: [pradzia, atstovavimas] },
    }) as ReturnType<typeof usePage>);
    expect(mount(QuickAccess).findAll('a').map(link => link.attributes('href')))
      .toEqual(['/mocked-route/dashboard.atstovavimas']);

    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: {} },
      adminNavigation: { workspaces: [pradzia, rezervacijos] },
    }) as ReturnType<typeof usePage>);
    expect(mount(QuickAccess).findAll('a').map(link => link.attributes('href')))
      .toEqual(['/mocked-route/dashboard.reservations']);

    vi.mocked(usePage).mockReturnValue(createMockPage({
      auth: { can: {} },
      adminNavigation: { workspaces: [pradzia] },
    }) as ReturnType<typeof usePage>);
    expect(mount(QuickAccess).findAll('a')).toHaveLength(0);
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
      auth: { can: {} },
      adminNavigation: { workspaces: [pradzia, atstovavimas, rezervacijos] },
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
