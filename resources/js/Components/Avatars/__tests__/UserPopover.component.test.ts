import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePage } from '@inertiajs/vue3';

import type { AvatarSize } from '@/Components/ui/avatar';
import { createMockPage } from '@/tests/helpers/createMockPage';
import { commonStubs } from '@/tests/stubs';

import UserPopover, { type PopoverUser } from '../UserPopover.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const adminPage = () => createMockPage({
  app: { path: 'mano/users' },
  auth: {
    can: {
      index: { user: true },
    },
  },
});

const publicPage = () => createMockPage({
  app: { path: 'lt/naujienos' },
  auth: {
    can: {
      index: { user: true },
    },
  },
});

const adminPageWithoutPermission = () => createMockPage({
  app: { path: 'mano/users' },
  auth: {
    can: {
      index: { user: false },
    },
  },
});

const makeUser = (overrides: Partial<PopoverUser> = {}): PopoverUser => ({
  id: 'u1',
  name: 'Jonas Jonaitis',
  ...overrides,
});

const hoverCardStubs = {
  ...commonStubs,
  HoverCard: { template: '<div data-stub="hover-card"><slot /></div>' },
  HoverCardTrigger: { template: '<div data-stub="hover-card-trigger"><slot /></div>' },
  HoverCardContent: { template: '<div data-stub="hover-card-content"><slot /></div>' },
};

const avatarStub = { props: ['user', 'size', 'interactive'], template: '<span class="user-avatar" />' };

interface MountPopoverProps {
  user?: PopoverUser;
  showName?: boolean;
  size?: number | AvatarSize;
  clickable?: boolean;
}

const mountPopover = (props: MountPopoverProps = {}, extraStubs: Record<string, unknown> = { UserAvatar: avatarStub }) => mount(UserPopover, {
  props: { user: makeUser(), ...props },
  global: {
    stubs: {
      ...hoverCardStubs,
      ISimpleIconsFacebook: { template: '<span />' },
      ...extraStubs,
    },
  },
});

describe('UserPopover', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  it.each([false, true])('renders compact avatars with readable initials (showName: %s)', (showName) => {
    const wrapper = mountPopover({ size: 'xxs', showName, clickable: false }, {});

    const avatar = wrapper.find('[data-slot="avatar"]');
    expect(avatar.classes()).toContain('size-5');
    expect(avatar.text()).toBe('JJ');
    expect(avatar.find('[data-slot="avatar-fallback"]').classes()).toContain('text-xs');
    if (showName) {
      expect(wrapper.find('[data-stub="hover-card-trigger"]').text()).toContain('Jonas Jonaitis');
    }
  });

  it('renders the avatar trigger as a link in admin context with permission and id', () => {
    vi.mocked(usePage).mockReturnValue(adminPage());

    const wrapper = mountPopover({});

    const link = wrapper.find('[data-testid="inertia-link"]');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toContain('users.show');
    expect(link.find('.user-avatar').exists()).toBe(true);
  });

  it('renders the name trigger as a link in admin context with permission and id', () => {
    vi.mocked(usePage).mockReturnValue(adminPage());

    const wrapper = mountPopover({ showName: true });

    const link = wrapper.find('[data-testid="inertia-link"]');
    expect(link.exists()).toBe(true);
    expect(link.text()).toContain('Jonas Jonaitis');
    expect(link.attributes('href')).toContain('users.show');
  });

  it('does not render a link outside the admin context', () => {
    vi.mocked(usePage).mockReturnValue(publicPage());

    const wrapper = mountPopover({});

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
    expect(wrapper.find('[data-stub="hover-card-trigger"] div').exists()).toBe(true);
  });

  it('does not render a link without user view permission', () => {
    vi.mocked(usePage).mockReturnValue(adminPageWithoutPermission());

    const wrapper = mountPopover({});

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
  });

  it('does not render a link when the user has no id', () => {
    vi.mocked(usePage).mockReturnValue(adminPage());

    const wrapper = mountPopover({ user: makeUser({ id: undefined }) });

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
  });

  it('allows explicit opt-out with clickable=false in admin context', () => {
    vi.mocked(usePage).mockReturnValue(adminPage());

    const wrapper = mountPopover({ clickable: false });

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
  });

  it('does not force linking when clickable=true outside admin context or without permission', () => {
    vi.mocked(usePage).mockReturnValue(publicPage());
    expect(mountPopover({ clickable: true }).find('[data-testid="inertia-link"]').exists()).toBe(false);

    vi.mocked(usePage).mockReturnValue(adminPageWithoutPermission());
    expect(mountPopover({ clickable: true }).find('[data-testid="inertia-link"]').exists()).toBe(false);
  });
});
