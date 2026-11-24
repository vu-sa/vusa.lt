import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { usePage } from '@inertiajs/vue3';

import { createMockPage } from '@/tests/helpers/createMockPage';

import DutyHolderCard from '../DutyHolderCard.vue';

vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));

const stubs = {
  UserPopover: {
    props: ['user', 'clickable'],
    template: '<div class="user-popover" :data-clickable="String(clickable)" />',
  },
};

const makeMember = (pivot: Record<string, unknown>) => ({
  id: '1',
  name: 'Jane Doe',
  pivot,
}) as App.Entities.User;

describe('DutyHolderCard', () => {
  beforeEach(() => {
    vi.mocked(usePage).mockReturnValue(createMockPage());
  });

  it('renders the holder name', () => {
    const wrapper = mount(DutyHolderCard, {
      props: { member: makeMember({ start_date: '2023-09-01', end_date: null, tenant_id: null }) },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Jane Doe');
  });

  it('shows an open-ended tenure as "dabar"', () => {
    const wrapper = mount(DutyHolderCard, {
      props: { member: makeMember({ start_date: '2023-09-01', end_date: null, tenant_id: null }) },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('dabar');
  });

  it('marks cross-tenant reps as delegated', () => {
    const wrapper = mount(DutyHolderCard, {
      props: { member: makeMember({ start_date: '2023-09-01', end_date: null, tenant_id: 5 }) },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Deleguota');
  });

  it('does not show a delegated badge for owning-tenant reps', () => {
    const wrapper = mount(DutyHolderCard, {
      props: { member: makeMember({ start_date: '2023-09-01', end_date: null, tenant_id: null }) },
      global: { stubs },
    });
    expect(wrapper.text()).not.toContain('Deleguota');
  });

  it('links the card in admin context with permission and opts the nested UserPopover out', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: { path: 'mano/duties/1' },
      auth: { can: { index: { user: true } } },
    }));

    const wrapper = mount(DutyHolderCard, {
      props: { member: makeMember({ start_date: '2023-09-01', end_date: null, tenant_id: null }) },
      global: { stubs },
    });

    const link = wrapper.find('[data-testid="inertia-link"]');
    expect(link.exists()).toBe(true);
    expect(link.attributes('href')).toContain('users.show');
    expect(wrapper.get('.user-popover').attributes('data-clickable')).toBe('false');
  });

  it('renders a non-link container without user view permission', () => {
    vi.mocked(usePage).mockReturnValue(createMockPage({
      app: { path: 'mano/duties/1' },
      auth: { can: { index: { user: false } } },
    }));

    const wrapper = mount(DutyHolderCard, {
      props: { member: makeMember({ start_date: '2023-09-01', end_date: null, tenant_id: null }) },
      global: { stubs },
    });

    expect(wrapper.find('[data-testid="inertia-link"]').exists()).toBe(false);
    expect(wrapper.get('.user-popover').attributes('data-clickable')).toBe('false');
  });
});
