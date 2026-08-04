import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';

import { commonStubs } from '@/tests/stubs';

import ContactCard from '../ContactWithPhoto.vue';

// InfoPopover is stubbed to a marker so presence/absence reflects the component's
// own v-if decision (the gating logic is what we want to assert, not the popover internals).
const stubs = {
  ...commonStubs,
  InfoPopover: { template: '<span class="info-popover" />' },
  IFluentMail20Regular: { template: '<span class="icon-mail" />' },
  IFluentPhone20Regular: { template: '<span class="icon-phone" />' },
  ISimpleIconsFacebook: { template: '<span class="icon-facebook" />' },
  // Render the trigger inline but keep the content hidden — mirrors real popover
  // behaviour where additional emails are not in the DOM until opened.
  Popover: { template: '<div class="popover"><slot /></div>' },
  PopoverTrigger: { template: '<div class="popover-trigger"><slot /></div>' },
  PopoverContent: { template: '<div class="popover-content" />' },
};

const makeContact = (overrides: Record<string, unknown> = {}) => ({
  id: 'u1',
  name: 'Jonas Jonaitis',
  email: 'jonas@vusa.lt',
  phone: null,
  facebook_url: null,
  show_pronouns: false,
  pronouns: null,
  duties: [],
  ...overrides,
}) as any;

const makeDuty = (overrides: Record<string, unknown> = {}) => ({
  id: 'd1',
  name: 'Pirmininkas',
  description: null,
  email: null,
  pivot: { additional_email: null, description: null },
  ...overrides,
}) as any;

const mountCard = (contact: any, duties: any[], options: Record<string, unknown> = {}) =>
  mount(ContactCard, {
    props: { contact, duties, ...options },
    global: { stubs },
  });

describe('ContactWithPhoto', () => {
  let wrapper: ReturnType<typeof mount>;

  describe('duty info popover', () => {
    it('hides the popover when the pivot description is an empty paragraph', () => {
      // Regression: duty.description is non-empty, so the old v-if showed the button,
      // but the rendered (pivot) description was "<p></p>" — an empty popover.
      wrapper = mountCard(makeContact(), [
        makeDuty({ description: '<p>Real duty description</p>', pivot: { description: '<p></p>' } }),
      ]);

      expect(wrapper.find('.info-popover').exists()).toBe(false);
    });

    it('shows the popover when only the pivot carries a description', () => {
      // Inverse of the bug: duty.description is null, but pivot has real content.
      wrapper = mountCard(makeContact(), [
        makeDuty({ description: null, pivot: { description: '<p>Pivot-only info</p>' } }),
      ]);

      expect(wrapper.find('.info-popover').exists()).toBe(true);
    });

    it('hides the popover when both descriptions are empty or null', () => {
      wrapper = mountCard(makeContact(), [
        makeDuty({ description: '<p></p>', pivot: { description: '<p><br></p>' } }),
      ]);

      expect(wrapper.find('.info-popover').exists()).toBe(false);
    });
  });

  describe('email display', () => {
    it('shows the primary email as a visible mailto link', () => {
      wrapper = mountCard(makeContact({ email: 'jonas@vusa.lt' }), [
        makeDuty({ email: 'pirmininkas@vusa.lt' }),
      ]);

      const mailto = wrapper.find('a[href^="mailto:"]');
      expect(mailto.exists()).toBe(true);
      // Precedence: pivot.additional_email ?? duty.email ?? contact.email
      expect(mailto.attributes('href')).toBe('mailto:pirmininkas@vusa.lt');
      expect(mailto.text()).toContain('pirmininkas@vusa.lt');
      // Single email → no "+N" affordance.
      expect(wrapper.findAll('button').some(b => b.text().includes('+'))).toBe(false);
    });

    it('offers a +N control for additional emails while keeping the primary visible', () => {
      wrapper = mountCard(makeContact(), [
        makeDuty({ id: 'd1', email: 'pirm@vusa.lt' }),
        makeDuty({ id: 'd2', email: 'antr@vusa.lt' }),
      ]);

      expect(wrapper.find('a[href^="mailto:"]').attributes('href')).toBe('mailto:pirm@vusa.lt');
      expect(wrapper.findAll('button').some(b => b.text().includes('+1'))).toBe(true);
    });
  });

  describe('empty state', () => {
    it('renders neither email nor action row when there are no duties or contact links', () => {
      wrapper = mountCard(makeContact({ phone: null, facebook_url: null }), []);

      expect(wrapper.find('a[href^="mailto:"]').exists()).toBe(false);
      expect(wrapper.find('.info-popover').exists()).toBe(false);
      // Action row is conditional on phone / facebook.
      expect(wrapper.find('a[href^="tel:"]').exists()).toBe(false);
    });
  });
});
