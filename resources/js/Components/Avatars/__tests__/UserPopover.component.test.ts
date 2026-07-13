import { mount } from '@vue/test-utils';
import { describe, it, expect, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import UserPopover from '../UserPopover.vue';

import { createMockPage } from '@/tests/helpers/createMockPage';

const adminPage = createMockPage({
  app: { path: '/mano/users' },
  auth: {
    can: {
      'users.read.padalinys': true,
    },
  },
});

const publicPage = createMockPage({
  app: { path: '/lt/naujienos' },
  auth: {
    can: {
      'users.read.padalinys': true,
    },
  },
});

const adminPageWithoutPermission = createMockPage({
  app: { path: '/mano/users' },
  auth: {
    can: {
      'users.read.padalinys': false,
    },
  },
});

const makeUser = (overrides: Record<string, any> = {}) => ({
  id: 'u1',
  name: 'Jonas Jonaitis',
  ...overrides,
});

const hoverCardStubs = {
  HoverCard: { template: '<div><slot /></div>' },
  HoverCardTrigger: { template: '<div data-stub="hover-card-trigger"><slot /></div>' },
  HoverCardContent: { template: '<div data-stub="hover-card-content"><slot /></div>' },
};

const avatarStub = { props: ['user', 'size', 'interactive'], template: '<span class="user-avatar" />' };

describe('UserPopover', () => {
  it('renders the avatar trigger as a link in admin context with permission', () => {
    vi.mocked(usePage).mockReturnValue(adminPage);

    const wrapper = mount(UserPopover, {
      props: { user: makeUser() },
      global: {
        stubs: {
          ...hoverCardStubs,
          UserAvatar: avatarStub,
          IFluentMail20Regular: { template: '<span />' },
          IFluentPhone20Regular: { template: '<span />' },
          ISimpleIconsFacebook: { template: '<span />' },
        },
      },
    });

    const link = wrapper.find('[data-testid="inertia-link"]');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toContain('users.show');
    expect(link.find('.user-avatar').exists()).toBe(true);
  });

  it('renders the name trigger as a link in admin context with permission', () => {
    vi.mocked(usePage).mockReturnValue(adminPage);

    const wrapper = mount(UserPopover, {
      props: { user: makeUser(), showName: true },
      global: {
        stubs: {
          ...hoverCardStubs,
          UserAvatar: avatarStub,
          IFluentMail20Regular: { template: '<span />' },
          IFluentPhone20Regular: { template: '<span />' },
          ISimpleIconsFacebook: { template: '<span />' },
        },
      },
    });

    const link = wrapper.find('[data-testid="inertia-link"]');
    expect(link.exists()).toBe(true);
    expect(link.text()).toContain('Jonas Jonaitis');
    expect(link.attributes('href')).toContain('users.show');
  });

  it('does not render a link outside the admin context', () => {
    vi.mocked(usePage).mockReturnValue(publicPage);

    const wrapper = mount(UserPopover, {
      props: { user: makeUser() },
      global: {
        stubs: {
          ...hoverCardStubs,
          UserAvatar: avatarStub,
          IFluentMail20Regular: { template: '<span />' },
          IFluentPhone20Regular: { template: '<span />' },
          ISimpleIconsFacebook: { template: '<span />' },
        },
      },
    });

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
    expect(wrapper.find('[data-stub="hover-card-trigger"] div').exists()).toBe(true);
  });

  it('does not render a link without user view permission', () => {
    vi.mocked(usePage).mockReturnValue(adminPageWithoutPermission);

    const wrapper = mount(UserPopover, {
      props: { user: makeUser() },
      global: {
        stubs: {
          ...hoverCardStubs,
          UserAvatar: avatarStub,
          IFluentMail20Regular: { template: '<span />' },
          IFluentPhone20Regular: { template: '<span />' },
          ISimpleIconsFacebook: { template: '<span />' },
        },
      },
    });

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
  });

  it('does not render a link when the user has no id', () => {
    vi.mocked(usePage).mockReturnValue(adminPage);

    const wrapper = mount(UserPopover, {
      props: { user: makeUser({ id: undefined }) },
      global: {
        stubs: {
          ...hoverCardStubs,
          UserAvatar: avatarStub,
          IFluentMail20Regular: { template: '<span />' },
          IFluentPhone20Regular: { template: '<span />' },
          ISimpleIconsFacebook: { template: '<span />' },
        },
      },
    });

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
  });

  it('can be forced non-clickable in admin context', () => {
    vi.mocked(usePage).mockReturnValue(adminPage);

    const wrapper = mount(UserPopover, {
      props: { user: makeUser(), clickable: false },
      global: {
        stubs: {
          ...hoverCardStubs,
          UserAvatar: avatarStub,
          IFluentMail20Regular: { template: '<span />' },
          IFluentPhone20Regular: { template: '<span />' },
          ISimpleIconsFacebook: { template: '<span />' },
        },
      },
    });

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
  });

  it('can be forced clickable outside admin context', () => {
    vi.mocked(usePage).mockReturnValue(publicPage);

    const wrapper = mount(UserPopover, {
      props: { user: makeUser(), clickable: true },
      global: {
        stubs: {
          ...hoverCardStubs,
          UserAvatar: avatarStub,
          IFluentMail20Regular: { template: '<span />' },
          IFluentPhone20Regular: { template: '<span />' },
          ISimpleIconsFacebook: { template: '<span />' },
        },
      },
    });

    const link = wrapper.find('[data-testid="inertia-link"]');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toContain('users.show');
  });
});
