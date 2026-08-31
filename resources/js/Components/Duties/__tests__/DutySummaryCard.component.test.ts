import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';

import DutySummaryCard, { type SummaryDuty } from '../DutySummaryCard.vue';

const makeUser = (id: string, name: string) => ({ id, name }) as App.Entities.User;

const makeDuty = (overrides: Record<string, unknown> = {}): SummaryDuty => ({
  id: 'd1',
  name: 'Koordinatorius',
  places_to_occupy: 2,
  current_users: [makeUser('u1', 'Jane Doe'), makeUser('u2', 'John Roe')],
  ...overrides,
}) as SummaryDuty;

describe('DutySummaryCard', () => {
  it('renders the duty name', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty() },
    });
    expect(wrapper.text()).toContain('Koordinatorius');
  });

  /**
   * The staffing badge and holders row were removed on purpose — a member
   * profile is about the person, not how the duty is staffed. Keep this so
   * they don't creep back in with the props callers still pass.
   */
  it('renders no staffing status or holders row', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty() },
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
    });
    expect(wrapper.text()).toContain('personal@vusa.lt');
    expect(wrapper.text()).not.toContain('duty@vusa.lt');
  });

  it('stretches the duty link over the whole card', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty() },
    });

    // The ::after overlay is what makes the card itself clickable.
    expect(wrapper.find('a').classes()).toContain('after:inset-0');
  });

  it('sizes the meta icons against the text rather than in fixed pixels', () => {
    // Whether the icon then lands on the text's cap band is a real-layout
    // question jsdom cannot answer; the wiring is what is asserted here.
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty({ pivot: { start_date: '2024-07-01', additional_email: 'a@vusa.lt' } }) },
    });

    const metaIcons = wrapper.findAll('.icon-inline');
    expect(metaIcons.length).toBeGreaterThanOrEqual(2);
    expect(wrapper.findAll('[class*="h-3 w-3"]')).toHaveLength(0);
  });

  it('marks an open-ended tenure as ongoing', () => {
    const wrapper = mount(DutySummaryCard, {
      props: { duty: makeDuty({ pivot: { start_date: '2024-07-01', end_date: null } }) },
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
      });
      expect(wrapper.text()).toContain('Koordinatorė');
    });

    it('leaves the stored name unchanged when no holder is given', () => {
      const wrapper = mount(DutySummaryCard, {
        props: { duty: makeDuty({ name: 'Koordinatorius' }) },
      });
      expect(wrapper.text()).toContain('Koordinatorius');
      expect(wrapper.text()).not.toContain('Koordinatorė');
    });
  });
});
