import { mount } from '@vue/test-utils';
import { describe, it, expect } from 'vitest';

import UsersAvatarGroup from '../UsersAvatarGroup.vue';

import { commonStubs } from '@/tests/stubs';

const makeUsers = (n: number) =>
  Array.from({ length: n }, (_, i) => ({
    id: `u${i + 1}`,
    name: `User ${i + 1}`,
  }));

describe('UsersAvatarGroup', () => {
  it('does not render the expand control by default', () => {
    const wrapper = mount(UsersAvatarGroup, {
      props: { users: makeUsers(3) as any, max: 4 },
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
      props: { users: makeUsers(6) as any, max: 4, expandable: true },
      global: { stubs: { ...commonStubs, ...extraStubs } },
    });

    const content = wrapper.find('[data-stub="popover-content"]');
    expect(content.exists()).toBe(true);
    // The dropdown lists every user, not just the visible ones
    expect(content.text()).toContain('User 1');
    expect(content.text()).toContain('User 6');
  });
});
