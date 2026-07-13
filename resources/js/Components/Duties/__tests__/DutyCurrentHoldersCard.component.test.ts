import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DutyCurrentHoldersCard from '../DutyCurrentHoldersCard.vue';

const stubs = {
  DutyHolderCard: { props: ['member'], template: '<div class="holder">{{ member.name }}</div>' },
};

const makeHolder = (id: string, name: string) => ({ id, name, pivot: { start_date: '2024-09-01', end_date: null, tenant_id: null } }) as any;

describe('DutyCurrentHoldersCard', () => {
  it('renders the vacancy state when there are no holders', () => {
    const wrapper = mount(DutyCurrentHoldersCard, {
      props: { holders: [], placesToOccupy: 2, canAssign: true },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('Pareigos neužimtos');
    expect(wrapper.find('.holder').exists()).toBe(false);
  });

  it('renders a holder card per holder and the occupancy count', () => {
    const wrapper = mount(DutyCurrentHoldersCard, {
      props: { holders: [makeHolder('1', 'Jane'), makeHolder('2', 'John')], placesToOccupy: 3, canAssign: false },
      global: { stubs },
    });
    expect(wrapper.findAll('.holder')).toHaveLength(2);
    expect(wrapper.text()).toContain('2 / 3');
  });

  it('shows an open-seat affordance when seats remain and the user can assign', () => {
    const wrapper = mount(DutyCurrentHoldersCard, {
      props: { holders: [makeHolder('1', 'Jane')], placesToOccupy: 3, canAssign: true },
      global: { stubs },
    });
    // 2 remaining seats → pluralized key
    expect(wrapper.text()).toContain(':count laisvos vietos');
  });

  it('emits assign when the assign action is triggered', async () => {
    const wrapper = mount(DutyCurrentHoldersCard, {
      props: { holders: [], placesToOccupy: 1, canAssign: true },
      global: { stubs },
    });
    await wrapper.findAll('button').at(-1)!.trigger('click');
    expect(wrapper.emitted('assign')).toBeTruthy();
  });
});
