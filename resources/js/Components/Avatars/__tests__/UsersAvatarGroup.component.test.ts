import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs, stubPopover, stubPopoverContent } from '@/tests/stubs';

import UserPopover from '../UserPopover.vue';
import UsersAvatarGroup from '../UsersAvatarGroup.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const makeUsers = (n: number, overrides: Record<string, unknown> = {}): App.Entities.User[] =>
  Array.from({ length: n }, (_, i) => ({
    id: `u${i + 1}`,
    name: `User ${i + 1}`,
    ...overrides,
  })) as unknown as App.Entities.User[];

const adminPage = () => createMockPage({
  app: { path: '/mano/institutions/123' },
  auth: {
    can: {
      'users.read.padalinys': true,
    },
  },
});

const publicPage = () => createMockPage({
  app: { path: '/lt/naujienos' },
  auth: {
    can: {
      'users.read.padalinys': true,
    },
  },
});

const adminPageWithoutPermission = () => createMockPage({
  app: { path: '/mano/institutions/123' },
  auth: {
    can: {
      'users.read.padalinys': false,
    },
  },
});

const popoverStubs = {
  Popover: stubPopover,
  PopoverTrigger: { template: '<div data-stub="popover-trigger"><slot /></div>' },
  PopoverContent: stubPopoverContent,
};

const hoverCardStubs = {
  HoverCard: { template: '<div data-stub="hover-card"><slot /></div>' },
  HoverCardTrigger: { template: '<div data-stub="hover-card-trigger"><slot /></div>' },
  HoverCardContent: { template: '<div data-stub="hover-card-content"><slot /></div>' },
};

describe('UsersAvatarGroup', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  it('does not render a popover when users fit within max and expandable is false', () => {
    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(3), max: 4 },
      global: { stubs: commonStubs },
    });

    expect(wrapper.findComponent({ name: 'Popover' }).exists()).toBe(false);
  });

  it('delegates visible avatar linking to UserPopover under the one linking rule', () => {
    vi.mocked(usePage).mockReturnValue(adminPage());

    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(2), max: 4 },
      global: { stubs: { ...commonStubs, ...hoverCardStubs } },
    });

    expect(wrapper.findAllComponents(UserPopover)).toHaveLength(2);
    const links = wrapper.findAll('[data-testid="inertia-link"]');
    expect(links).toHaveLength(2);
    expect(links[0]?.attributes('href')).toContain('users.show');
  });

  it('does not link visible avatars without permission, outside admin, or with clickable=false', () => {
    vi.mocked(usePage).mockReturnValue(adminPageWithoutPermission());
    expect(mount(UsersAvatarGroup, {
      props: { users: makeUsers(2), max: 4, clickable: true },
      global: { stubs: { ...commonStubs, ...hoverCardStubs } },
    }).findAll('[data-testid="inertia-link"]')).toHaveLength(0);

    vi.mocked(usePage).mockReturnValue(publicPage());
    expect(mount(UsersAvatarGroup, {
      props: { users: makeUsers(2), max: 4, clickable: true },
      global: { stubs: { ...commonStubs, ...hoverCardStubs } },
    }).findAll('[data-testid="inertia-link"]')).toHaveLength(0);

    vi.mocked(usePage).mockReturnValue(adminPage());
    expect(mount(UsersAvatarGroup, {
      props: { users: makeUsers(2), max: 4, clickable: false },
      global: { stubs: { ...commonStubs, ...hoverCardStubs } },
    }).findAll('[data-testid="inertia-link"]')).toHaveLength(0);
  });

  it('opens a click popover on +N with overflow rows linked by the rule and no nested hover cards', () => {
    vi.mocked(usePage).mockReturnValue(adminPage());

    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(6), max: 4 },
      global: { stubs: { ...commonStubs, ...popoverStubs, ...hoverCardStubs } },
    });

    expect(wrapper.find('button[data-slot="users-avatar-group-overflow"]').text()).toContain('+2');
    const content = wrapper.get('.popover-content');
    expect(content.text()).toContain('User 5');
    expect(content.text()).toContain('User 6');
    expect(content.text()).not.toContain('User 1');

    // No hover cards nested inside the overflow popover list.
    expect(content.findAllComponents(UserPopover)).toHaveLength(0);
    expect(content.findAll('[data-stub="hover-card"]')).toHaveLength(0);

    const overflowLinks = content.findAll('[data-testid="inertia-link"]');
    expect(overflowLinks).toHaveLength(2);
    expect(overflowLinks[0]?.attributes('href')).toContain('users.show');
  });

  it.each([
    ['outside admin context', publicPage, makeUsers(6)],
    ['without permission', adminPageWithoutPermission, makeUsers(6)],
    ['when users have no id', adminPage, makeUsers(6, { id: undefined })],
  ])('does not link overflow rows %s', (_, getPage, users) => {
    vi.mocked(usePage).mockReturnValue(getPage());

    const wrapper = mount(UsersAvatarGroup, {
      props: { users, max: 4 },
      global: { stubs: { ...commonStubs, ...popoverStubs, ...hoverCardStubs } },
    });

    const content = wrapper.get('.popover-content');
    expect(content.findAll('li')).toHaveLength(2);
    expect(content.findAll('[data-testid="inertia-link"]')).toHaveLength(0);
    expect(content.findAllComponents(UserPopover)).toHaveLength(0);
  });

  it('renders an expandable single trigger with a bounded people list linked by the rule', () => {
    vi.mocked(usePage).mockReturnValue(adminPage());

    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(12), max: 4, expandable: true },
      global: { stubs: { ...commonStubs, ...popoverStubs, ...hoverCardStubs } },
    });

    const trigger = wrapper.get('button[data-slot="users-avatar-group"]');
    expect(trigger.classes()).toContain('hover:bg-secondary');
    expect(trigger.findAll('a')).toHaveLength(0);

    const content = wrapper.get('.popover-content');
    const list = content.get('ul');
    expect(list.classes()).toEqual(expect.arrayContaining(['max-h-72', 'overflow-y-auto', 'overscroll-contain']));
    expect(list.findAll('li')).toHaveLength(12);
    expect(content.text()).toContain('User 1');
    expect(content.text()).toContain('User 12');
    expect(content.findAll('[data-testid="inertia-link"]')).toHaveLength(12);
    expect(content.findAllComponents(UserPopover)).toHaveLength(0);
  });

  it('limits visible avatars on small screens when limitByScreen is true', () => {
    const originalMatchMedia = window.matchMedia;
    window.matchMedia = vi.fn().mockImplementation((query: string) => ({
      matches: query.includes('639'),
      media: query,
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    }));

    try {
      const wrapper = mount(UsersAvatarGroup, {
        props: { users: makeUsers(6), max: 4, limitByScreen: true },
        global: { stubs: commonStubs },
      });

      expect(wrapper.text()).toContain('+4');
    }
    finally {
      window.matchMedia = originalMatchMedia;
    }
  });

  it('keeps default max when limitByScreen is false', () => {
    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(6), max: 4, limitByScreen: false },
      global: { stubs: commonStubs },
    });

    expect(wrapper.text()).toContain('+2');
  });
});
