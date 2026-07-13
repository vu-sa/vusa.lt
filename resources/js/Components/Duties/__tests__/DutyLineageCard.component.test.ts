import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DutyLineageCard from '../DutyLineageCard.vue';

import { currentAcademicYear, formatAcademicYearLabel } from '@/Utils/IntlTime';

const stubs = {
  UsersAvatarGroup: { props: ['users'], template: '<div class="grp">{{ users.map(u => u.name).join(",") }}</div>' },
};

const year = new Date().getFullYear();

// Active member (no end date) spans from 3 academic years ago through today.
const activeMember = {
  id: 'a',
  name: 'Active',
  pivot: { start_date: new Date(year - 3, 9, 1).toISOString(), end_date: null, tenant_id: null },
} as any;

// Old member ended well in the past — only in an older bucket.
const oldMember = {
  id: 'o',
  name: 'Old',
  pivot: { start_date: new Date(year - 3, 9, 1).toISOString(), end_date: new Date(year - 2, 5, 1).toISOString(), tenant_id: null },
} as any;

describe('DutyLineageCard', () => {
  it('marks the current academic year and lists active members there', () => {
    const wrapper = mount(DutyLineageCard, {
      props: { members: [activeMember, oldMember] },
      global: { stubs },
    });

    const currentLabel = formatAcademicYearLabel(currentAcademicYear());
    expect(wrapper.text()).toContain(currentLabel);
    expect(wrapper.text()).toContain('Dabartiniai');

    // The first (current) bucket contains the active member, not the one that ended.
    const firstGroup = wrapper.find('.grp');
    expect(firstGroup.text()).toContain('Active');
    expect(firstGroup.text()).not.toContain('Old');
  });

  it('places a member who ended in the past into an older bucket', () => {
    const wrapper = mount(DutyLineageCard, {
      props: { members: [oldMember] },
      global: { stubs },
    });

    // The old member appears, but the current bucket is shown as vacant.
    expect(wrapper.text()).toContain('Old');
    expect(wrapper.text()).toContain('Neužimta');
  });
});
