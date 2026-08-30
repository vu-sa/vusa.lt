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

  it('hides the staffing badge and holders row when the caller opts out', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty(), showStatus: false, showHolders: false },
      global: { stubs },
    });
    expect(wrapper.text()).not.toContain('užimta');
    expect(wrapper.findComponent({ name: 'UsersAvatarGroup' }).exists()).toBe(false);
  });

  it('prefers the assignment email over the duty-wide one', () => {
    const wrapper = mount(DutySummaryCard, {
      props: {
        duty: makeDuty({
          email: 'duty@vusa.lt',
          pivot: { start_date: '2024-07-01', additional_email: 'personal@vusa.lt' },
        }),
      },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('personal@vusa.lt');
    expect(wrapper.text()).not.toContain('duty@vusa.lt');
  });

  it('stretches the duty link over the whole card, but not over the holders row', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty() },
      global: { stubs },
    });

    // The ::after overlay is what makes the card itself clickable.
    expect(wrapper.find('a').classes()).toContain('after:inset-0');
    // The avatar group has to stay above that overlay to keep its own popovers.
    expect(wrapper.findComponent({ name: 'UsersAvatarGroup' }).element.parentElement?.className)
      .toContain('z-10');
  });

  it('sizes the meta icons against the text rather than in fixed pixels', () => {
    // Whether the icon then lands on the text's cap band is a real-layout
    // question jsdom cannot answer; the wiring is what is asserted here.
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty({ pivot: { start_date: '2024-07-01', additional_email: 'a@vusa.lt' } }) },
      global: { stubs },
    });

    const metaIcons = wrapper.findAll('.icon-inline');
    expect(metaIcons.length).toBeGreaterThanOrEqual(2);
    expect(wrapper.findAll('[class*="h-3 w-3"]')).toHaveLength(0);
  });

  it('marks an open-ended tenure as ongoing', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty({ pivot: { start_date: '2024-07-01', end_date: null } }) },
      global: { stubs },
    });
    expect(wrapper.text()).toContain('dabar');
  });

  describe('holder-based inflection', () => {
    // The page locale defaults to 'lt' in the test setup.
    it('inflects the duty name to match a feminine holder', () => {
      const wrapper = mount(DutySummaryCard, {
        props: {
          duty: makeDuty({ name: 'Koordinatorius' }),
          holder: { name: 'Ona Onaitė', pronouns: 'ji/jos' },
        },
        global: { stubs },
      });
      expect(wrapper.text()).toContain('Koordinatorė');
    });

    it('leaves the stored name unchanged when no holder is given', () => {
      const wrapper = mount(DutySummaryCard, {
        props: { duty: makeDuty({ name: 'Koordinatorius' }) },
        global: { stubs },
      });
      expect(wrapper.text()).toContain('Koordinatorius');
      expect(wrapper.text()).not.toContain('Koordinatorė');
    });
  });
});
