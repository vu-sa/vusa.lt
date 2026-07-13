import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DutyHolderCard from '../DutyHolderCard.vue';

const stubs = { UserPopover: { template: '<div class="user-popover" />' } };

const makeMember = (pivot: Record<string, unknown>) => ({
  id: '1',
  name: 'Jane Doe',
  pivot,
}) as any;

describe('DutyHolderCard', () => {
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
    // $t is mocked to return the key
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
});
