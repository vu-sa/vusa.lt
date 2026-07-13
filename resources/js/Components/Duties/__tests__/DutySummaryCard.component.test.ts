import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DutySummaryCard from '../DutySummaryCard.vue';

const stubs = {
  UsersAvatarGroup: {
    name: 'UsersAvatarGroup',
    props: { users: { type: Array, default: () => [] }, clickable: Boolean, max: Number, size: [Number, String] },
    template: '<div class="avatar-group" :data-count="users.length" />',
  },
};

const makeUser = (id: string, name: string) => ({ id, name }) as any;

const makeDuty = (overrides: Record<string, unknown> = {}) => ({
  id: 'd1',
  name: 'Koordinatorius',
  places_to_occupy: 2,
  current_users: [makeUser('u1', 'Jane Doe'), makeUser('u2', 'John Roe')],
  ...overrides,
}) as any;

describe('DutySummaryCard', () => {
  it('renders the duty name and holder count', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty() },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Koordinatorius');
    expect(wrapper.text()).toContain('2 / 2');
  });

  it('passes holders to a clickable avatar group', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty() },
      global: { stubs },
    });
    const group = wrapper.findComponent({ name: 'UsersAvatarGroup' });
    expect(group.exists()).toBe(true);
    expect(group.props('clickable')).toBe(true);
    expect(group.props('users')).toHaveLength(2);
  });

  it('excludes the viewed user from the holders avatar group', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty(), excludeUserId: 'u1' },
      global: { stubs },
    });
    const group = wrapper.findComponent({ name: 'UsersAvatarGroup' });
    expect(group.props('users')).toHaveLength(1);
    expect(group.props('users')[0].id).toBe('u2');
    // Count still reflects the true total, not the filtered avatars.
    expect(wrapper.text()).toContain('2 / 2');
  });

  it('shows a vacant status when there are no holders', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty({ current_users: [] }) },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Neužimta');
  });

  it('shows a partial status when under capacity', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty({ current_users: [makeUser('u1', 'Jane Doe')] }) },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Dalinai užimta');
  });
});
