import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import UsersAvatarGroup from '../UsersAvatarGroup.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

const makeUsers = (n: number): App.Entities.User[] =>
  Array.from({ length: n }, (_, i) => ({
    id: `u${i + 1}`,
    name: `User ${i + 1}`,
  })) as unknown as App.Entities.User[];

const adminPage = createMockPage({
  app: { path: '/mano/institutions/123' },
  auth: {
    can: {
      'users.read.padalinys': true,
    },
  },
});

describe('UsersAvatarGroup', () => {
  it('does not render the expand control by default', () => {
    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(3), max: 4 },
      global: { stubs: commonStubs },
    });
    expect(wrapper.findComponent({ name: 'Popover' }).exists()).toBe(false);
  });

  it('renders an expandable dropdown listing all users', () => {
    const extraStubs = {
      Popover: { template: '<div data-stub="popover"><slot /></div>' },
      PopoverTrigger: { template: '<div><slot /></div>' },
      PopoverContent: { template: '<div data-stub="popover-content"><slot /></div>' },
      // Stub UserPopover so its name renders as plain text (the real one nests in a HoverCard)
      UserPopover: { props: ['user'], template: '<div class="user-row">{{ user?.name }}</div>' },
    };

    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(6), max: 4, expandable: true },
      global: { stubs: { ...commonStubs, ...extraStubs } },
    });

    const content = wrapper.find('[data-stub="popover-content"]');
    expect(content.exists()).toBe(true);
    // The dropdown lists every user, not just the visible ones
    expect(content.text()).toContain('User 1');
    expect(content.text()).toContain('User 6');
  });

  it('wraps visible avatars in a link to the user page when clickable', () => {
    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(2), max: 4, clickable: true },
      global: {
        stubs: {
          ...commonStubs,
          UserPopover: { props: ['user'], template: '<div class="user-row">{{ user?.name }}</div>' },
        },
      },
    });
    const links = wrapper.findAll('[data-testid="inertia-link"]');
    expect(links.length).toBe(2);
    expect(links[0]?.attributes('href')).toContain('users.show');
  });

  it('does not wrap avatars in links by default', () => {
    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(2), max: 4 },
      global: {
        stubs: {
          ...commonStubs,
          UserPopover: { props: ['user'], template: '<div class="user-row">{{ user?.name }}</div>' },
        },
      },
    });
    expect(wrapper.findAll('[data-testid="inertia-link"]').length).toBe(0);
  });

  it('auto-links avatars when rendered in the admin context', () => {
    vi.mocked(usePage).mockReturnValue(adminPage);

    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(2), max: 4 },
      global: {
        stubs: {
          ...commonStubs,
          UserPopover: { props: ['user'], template: '<div class="user-row">{{ user?.name }}</div>' },
        },
      },
    });

    const links = wrapper.findAll('[data-testid="inertia-link"]');
    expect(links.length).toBe(2);
    expect(links[0]?.attributes('href')).toContain('users.show');
  });

  it('passes clickable=false to internal UserPopover instances to avoid nested links', () => {
    vi.mocked(usePage).mockReturnValue(adminPage);

    const userPopoverStub = { props: ['user', 'clickable'], template: '<div class="user-row" :data-clickable="clickable">{{ user?.name }}</div>' };

    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(2), max: 4, clickable: true },
      global: {
        stubs: {
          ...commonStubs,
          UserPopover: userPopoverStub,
        },
      },
    });

    const rows = wrapper.findAll('.user-row');
    expect(rows.length).toBe(2);
    expect(rows.every(row => row.attributes('data-clickable') === 'false')).toBe(true);
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

      // On small screens, maxVisibleUsers is capped at 2, remaining is 6 - 2 = 4
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

    // With max 4, remaining is 6 - 4 = 2
    expect(wrapper.text()).toContain('+2');
  });
});
